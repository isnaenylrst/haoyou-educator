<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ClassScheduleSeeder extends Seeder
{
    public const ROOMS = ['Beijing', 'Basement', 'Atas', 'Kaca'];

    // Jam operasional di sheet: 09.30 - 21.00 (dalam menit sejak 00.00)
    public const OPENING_MINUTES = 9 * 60 + 30;
    public const CLOSING_MINUTES = 21 * 60;

    public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /** Pola hari per jumlah pertemuan per minggu (hanya hari operasional). */
    private const DAY_PATTERNS = [
        1 => [['Senin'], ['Selasa'], ['Rabu'], ['Kamis'], ['Jumat'], ['Sabtu']],
        2 => [['Senin', 'Rabu'], ['Selasa', 'Kamis'], ['Rabu', 'Jumat'], ['Senin', 'Kamis'], ['Selasa', 'Jumat']],
        3 => [['Senin', 'Rabu', 'Jumat']],
    ];

    // Jam mulai kandidat; slot yang keluar dari jam operasional otomatis dilewati
    private const WEEKDAY_TIMES = ['15:00', '16:30', '18:00'];
    private const WEEKEND_TIMES = ['09:30', '11:00', '13:00'];

    /** Jam terpakai: [kunci][hari] => list [menitMulai, menitSelesai, tglMulai, tglSelesai]. */
    private array $teacherBooked = [];
    private array $roomBooked    = [];

    public function run(): void
    {
        $this->loadExistingBookings();

        $classes = ClassModel::whereNotNull('teacher_id')
            ->whereDoesntHave('allSchedules')
            ->orderBy('id')
            ->get();

        foreach ($classes as $class) {
            $package = $class->package();

            if (! $package) {
                continue;
            }

            $minutes = (int) ($package->duration_minutes ?: 60);
            $months  = max((int) ($package->duration_months ?: 1), 1);
            $perWeek = (int) max(1, min(3, round(((int) $package->total_meetings) / ($months * 4))));
            $online  = $class->delivery_mode === 'Online';

            // Periode jadwal: kelas yang sudah berakhir ditutup di end_date, sisanya terbuka
            $from  = $this->dateStr($class->start_date);
            $until = ($class->end_date && Carbon::parse($class->end_date)->isPast())
                ? $this->dateStr($class->end_date)
                : null;

            $slots = $this->findSlots((int) $class->teacher_id, $perWeek, $minutes, $online, $from, $until);

            if (! $slots) {
                $this->command?->warn("Jadwal untuk \"{$class->class_name}\" tidak dibuat: tidak ada slot kosong.");

                continue;
            }

            foreach ($slots as $slot) {
                ClassSchedule::create([
                    'class_id'        => $class->id,
                    'day'             => $slot['day'],
                    'start_time'      => $slot['start'] . ':00',
                    'end_time'        => $slot['end'] . ':00',
                    'room'            => $slot['room'],
                    'effective_from'  => $from,
                    'effective_until' => $until,
                ]);

                $this->book((int) $class->teacher_id, $slot['room'], $slot['day'], $slot['start'], $slot['end'], $from, $until);
            }
        }
    }

    /** Cari pola hari + jam yang bebas untuk guru (dan ruangan kalau offline) pada periode kelas. */
    private function findSlots(int $teacherId, int $perWeek, int $minutes, bool $online, ?string $from, ?string $until): ?array
    {
        $patterns = self::DAY_PATTERNS[$perWeek];
        shuffle($patterns); // variasi hari supaya jadwal tidak menumpuk di Senin

        foreach ($patterns as $days) {
            $times = in_array('Sabtu', $days, true) ? self::WEEKEND_TIMES : self::WEEKDAY_TIMES;
            shuffle($times);

            foreach ($times as $start) {
                $end = Carbon::createFromFormat('H:i', $start)->addMinutes($minutes)->format('H:i');

                // Harus di dalam jam operasional
                if ($this->minutes($start) < self::OPENING_MINUTES || $this->minutes($end) > self::CLOSING_MINUTES) {
                    continue;
                }

                // Guru harus bebas di semua hari pola ini
                $teacherFree = collect($days)->every(
                    fn ($day) => ! $this->clashes($this->teacherBooked["t{$teacherId}"][$day] ?? [], $start, $end, $from, $until)
                );

                if (! $teacherFree) {
                    continue;
                }

                // Ruangan: satu ruangan yang bebas di semua hari (online tidak butuh ruangan)
                $room = 'Online';

                if (! $online) {
                    $room = collect(self::ROOMS)->first(
                        fn ($candidate) => collect($days)->every(
                            fn ($day) => ! $this->clashes($this->roomBooked[$candidate][$day] ?? [], $start, $end, $from, $until)
                        )
                    );

                    if (! $room) {
                        continue;
                    }
                }

                return collect($days)
                    ->map(fn ($day) => ['day' => $day, 'start' => $start, 'end' => $end, 'room' => $room])
                    ->all();
            }
        }

        return null;
    }

    private function loadExistingBookings(): void
    {
        ClassSchedule::join('classes', 'classes.id', '=', 'class_schedules.class_id')
            ->select(
                'class_schedules.day',
                'class_schedules.start_time',
                'class_schedules.end_time',
                'class_schedules.room',
                'class_schedules.effective_from',
                'class_schedules.effective_until',
                'classes.teacher_id'
            )
            ->get()
            ->each(fn ($row) => $this->book(
                (int) $row->teacher_id,
                $row->room,
                $row->day,
                substr($row->start_time, 0, 5),
                substr($row->end_time, 0, 5),
                $this->dateStr($row->effective_from),
                $this->dateStr($row->effective_until)
            ));
    }

    private function book(int $teacherId, ?string $room, string $day, string $start, string $end, ?string $from, ?string $until): void
    {
        $range = [$this->minutes($start), $this->minutes($end), $from, $until];

        $this->teacherBooked["t{$teacherId}"][$day][] = $range;

        if ($room && $room !== 'Online') {
            $this->roomBooked[$room][$day][] = $range;
        }
    }

    /** Bentrok = jam beririsan DAN periode tanggalnya beririsan. */
    private function clashes(array $booked, string $start, string $end, ?string $from, ?string $until): bool
    {
        $s = $this->minutes($start);
        $e = $this->minutes($end);

        foreach ($booked as [$bs, $be, $bFrom, $bUntil]) {
            if ($s < $be && $e > $bs && $this->periodsOverlap($from, $until, $bFrom, $bUntil)) {
                return true;
            }
        }

        return false;
    }

    private function periodsOverlap(?string $aFrom, ?string $aUntil, ?string $bFrom, ?string $bUntil): bool
    {
        $aFrom  ??= '0000-01-01';
        $bFrom  ??= '0000-01-01';
        $aUntil ??= '9999-12-31';
        $bUntil ??= '9999-12-31';

        return $aFrom <= $bUntil && $bFrom <= $aUntil;
    }

    private function dateStr($value): ?string
    {
        return $value ? Carbon::parse($value)->toDateString() : null;
    }

    private function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }
}