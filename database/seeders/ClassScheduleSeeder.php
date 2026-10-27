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

    /** Jam terpakai: [kunci][hari] => list [menitMulai, menitSelesai]. */
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

            $slots = $this->findSlots((int) $class->teacher_id, $perWeek, $minutes, $online);

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
                    'effective_from'  => $class->start_date?->toDateString(),
                    'effective_until' => null,
                ]);

                $this->book((int) $class->teacher_id, $slot['room'], $slot['day'], $slot['start'], $slot['end']);
            }
        }
    }

    /** Cari pola hari + jam yang bebas untuk guru (dan ruangan kalau offline). */
    private function findSlots(int $teacherId, int $perWeek, int $minutes, bool $online): ?array
    {
        foreach (self::DAY_PATTERNS[$perWeek] as $days) {
            $times = in_array('Sabtu', $days, true) ? self::WEEKEND_TIMES : self::WEEKDAY_TIMES;

            foreach ($times as $start) {
                $end = Carbon::createFromFormat('H:i', $start)->addMinutes($minutes)->format('H:i');

                // Harus di dalam jam operasional
                if ($this->minutes($start) < self::OPENING_MINUTES || $this->minutes($end) > self::CLOSING_MINUTES) {
                    continue;
                }

                // Guru harus bebas di semua hari pola ini
                $teacherFree = collect($days)->every(
                    fn ($day) => ! $this->clashes($this->teacherBooked["t{$teacherId}"][$day] ?? [], $start, $end)
                );

                if (! $teacherFree) {
                    continue;
                }

                // Ruangan: satu ruangan yang bebas di semua hari (online tidak butuh ruangan)
                $room = 'Online';

                if (! $online) {
                    $room = collect(self::ROOMS)->first(
                        fn ($candidate) => collect($days)->every(
                            fn ($day) => ! $this->clashes($this->roomBooked[$candidate][$day] ?? [], $start, $end)
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
            ->whereNull('class_schedules.effective_until')
            ->select('class_schedules.day', 'class_schedules.start_time', 'class_schedules.end_time', 'class_schedules.room', 'classes.teacher_id')
            ->get()
            ->each(fn ($row) => $this->book(
                (int) $row->teacher_id,
                $row->room,
                $row->day,
                substr($row->start_time, 0, 5),
                substr($row->end_time, 0, 5)
            ));
    }

    private function book(int $teacherId, ?string $room, string $day, string $start, string $end): void
    {
        $range = [$this->minutes($start), $this->minutes($end)];

        $this->teacherBooked["t{$teacherId}"][$day][] = $range;

        if ($room && $room !== 'Online') {
            $this->roomBooked[$room][$day][] = $range;
        }
    }

    private function clashes(array $booked, string $start, string $end): bool
    {
        $s = $this->minutes($start);
        $e = $this->minutes($end);

        foreach ($booked as [$bs, $be]) {
            if ($s < $be && $e > $bs) {
                return true;
            }
        }

        return false;
    }

    private function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }
}