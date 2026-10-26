<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\TeacherAvailability;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ClassScheduleService
{
    public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    public const ACTIVE_STATUSES = ['Open', 'Running'];

    /** Ruangan mengikuti mode kelas + cek jadwal bertabrakan dalam form yang sama. */
    public function normalizeSchedules(array $rows, string $mode): array
    {
        $errors = [];
        $out = [];

        foreach ($rows as $i => $row) {
            $row['room'] = isset($row['room']) ? trim($row['room']) : null;

            if ($mode === 'Online') {
                $row['room'] = 'Online';
            } elseif (empty($row['room']) || $row['room'] === 'Online') {
                $errors["schedules.$i.room"] = 'Pilih ruangan untuk kelas offline.';
            }
            $out[] = $row;
        }

        foreach ($out as $i => $a) {
            foreach ($out as $j => $b) {
                if ($j <= $i || $a['day'] !== $b['day']) {
                    continue;
                }
                if ($a['start_time'] < $b['end_time'] && $b['start_time'] < $a['end_time']) {
                    $errors['schedules'] = "Jadwal {$a['day']} saling bertabrakan.";
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $out;
    }

    /**
     * Setiap slot mingguan harus berada di dalam jam tersedia guru (teacher_availabilities).
     * Guru yang belum punya jam tersedia sama sekali dianggap tidak bisa dijadwalkan.
     */
    public function assertTeacherAvailable(int $teacherId, array $schedules): void
    {
        $slots = TeacherAvailability::where('teacher_id', $teacherId)->get();

        if ($slots->isEmpty()) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Guru ini belum mengisi jadwal tersedia, jadi belum bisa dijadwalkan.',
            ]);
        }

        foreach ($schedules as $row) {
            $covered = $slots->contains(
                fn (TeacherAvailability $a) => $a->covers($row['day'], $row['start_time'], $row['end_time'])
            );

            if (! $covered) {
                throw ValidationException::withMessages([
                    'teacher_id' => "Guru tidak tersedia pada {$row['day']} {$row['start_time']}–{$row['end_time']}. Cek jadwal tersedia guru.",
                ]);
            }
        }
    }

    /** Lempar error kalau guru sudah mengajar di jam yang sama (pola mingguan antar kelas). */
    public function assertTeacherFree(int $teacherId, array $schedules, ?int $exceptClassId = null): void
    {
        foreach ($schedules as $row) {
            $clash = ClassModel::query()
                ->where('teacher_id', $teacherId)
                ->whereIn('status', self::ACTIVE_STATUSES)
                ->when($exceptClassId, fn ($q) => $q->where('id', '!=', $exceptClassId))
                ->whereHas('schedules', fn ($q) => $q
                    ->where('day', $row['day'])
                    ->where('start_time', '<', $row['end_time'] . ':00')
                    ->where('end_time', '>', $row['start_time'] . ':00'))
                ->first(['id', 'class_name']);

            if ($clash) {
                throw ValidationException::withMessages([
                    'teacher_id' => "Guru sudah mengajar di kelas \"{$clash->class_name}\" pada {$row['day']} {$row['start_time']}–{$row['end_time']}.",
                ]);
            }
        }
    }

    /**
     * Cek bentrok guru pada TANGGAL tertentu terhadap pertemuan terjadwal (class_sessions).
     * Menangkap hal yang tidak terlihat dari pola mingguan: pertemuan yang dipindah
     * dan kelas pengganti. Pertemuan batal tidak dihitung.
     */
    public function assertTeacherFreeOn(
        int $teacherId,
        Carbon|string $date,
        string $start,
        string $end,
        ?int $ignoreSessionId = null,
        ?int $ignoreClassId = null,
    ): void {
        $date  = Carbon::parse($date);
        $start = substr($start, 0, 5) . ':00';
        $end   = substr($end, 0, 5) . ':00';

        $clash = ClassSession::query()
            ->where('teacher_id', $teacherId)
            ->whereDate('session_date', $date)
            ->overlapping($start, $end)
            ->when($ignoreSessionId, fn ($q) => $q->where('id', '!=', $ignoreSessionId))
            ->when($ignoreClassId, fn ($q) => $q->where(
                fn ($w) => $w->whereNull('class_id')->orWhere('class_id', '!=', $ignoreClassId)
            ))
            ->with('class:id,class_name')
            ->first();

        if ($clash) {
            $label = $clash->class?->class_name ?? 'kelas pengganti';

            throw ValidationException::withMessages([
                'teacher_id' => 'Guru sudah punya pertemuan ("' . $label . '") pada '
                    . $date->copy()->locale('id')->translatedFormat('j F Y') . ' jam '
                    . substr($start, 0, 5) . '–' . substr($end, 0, 5) . '.',
            ]);
        }
    }

    /**
     * Peta jadwal guru untuk ditampilkan ke admin saat menjadwalkan kelas.
     *   availability[teacher_id][hari] = daftar jam tersedia
     *   booked[teacher_id][hari]       = jam yang sudah terpakai kelas aktif (pola mingguan yang berlaku)
     *
     * $exceptClassId: kelas yang diabaikan (mis. kelas lama siswa yang akan ditutup saat lanjut program),
     * supaya slotnya tidak tampil sebagai bentrok.
     */
    public function teacherSlotsMap(?int $exceptClassId = null): array
    {
        $availability = TeacherAvailability::orderBy('start_time')->get()
            ->groupBy('teacher_id')
            ->map(fn ($rows) => $rows->groupBy('day')->map(
                fn ($day) => $day->map(fn ($a) => [
                    'start' => substr($a->start_time, 0, 5),
                    'end'   => substr($a->end_time, 0, 5),
                ])->values()
            ))
            ->toArray();

        $booked = ClassSchedule::whereNull('effective_until')
            ->when($exceptClassId, fn ($q) => $q->where('class_id', '!=', $exceptClassId))
            ->whereHas('class', fn ($q) => $q->whereNotNull('teacher_id')->whereIn('status', self::ACTIVE_STATUSES))
            ->with('class:id,teacher_id,class_name')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($s) => $s->class->teacher_id)
            ->map(fn ($rows) => $rows->groupBy('day')->map(
                fn ($day) => $day->map(fn ($s) => [
                    'start' => substr($s->start_time, 0, 5),
                    'end'   => substr($s->end_time, 0, 5),
                    'class' => $s->class->class_name,
                ])->values()
            ))
            ->toArray();

        return ['availability' => $availability, 'booked' => $booked];
    }

    /**
     * Simpan pola mingguan kelas. $effectiveFrom = tanggal pola mulai berlaku
     * (null = sejak awal). Pola tidak pernah dihapus, hanya ditutup lewat effective_until.
     */
    public function createSchedules(ClassModel $kelas, array $rows, Carbon|string|null $effectiveFrom = null): void
    {
        $from = $effectiveFrom ? Carbon::parse($effectiveFrom)->toDateString() : null;

        foreach ($rows as $row) {
            $kelas->schedules()->forceCreate([
                'class_id'       => $kelas->id,
                'day'            => $row['day'],
                'start_time'     => $row['start_time'],
                'end_time'       => $row['end_time'],
                'room'           => $row['room'] ?? null,
                'effective_from' => $from,
            ]);
        }
    }

    /** Tanggal pertemuan ke-N (N = total_meetings paket) sesuai jadwal mingguan. */
    public function resolveEndDate($package, string $startDate, array $schedules): ?string
    {
        $meetings = (int) ($package->total_meetings ?? 0);
        if ($meetings < 1 || empty($schedules)) {
            return null;
        }

        $dayMap = [
            'Senin' => Carbon::MONDAY, 'Selasa' => Carbon::TUESDAY, 'Rabu' => Carbon::WEDNESDAY,
            'Kamis' => Carbon::THURSDAY, 'Jumat' => Carbon::FRIDAY, 'Sabtu' => Carbon::SATURDAY,
            'Minggu' => Carbon::SUNDAY,
        ];
        $weekdays = array_unique(array_map(fn ($s) => $dayMap[$s['day']], $schedules));

        $date = Carbon::parse($startDate);
        $count = 0;
        for ($i = 0; $i < 3650; $i++) {
            if (in_array($date->dayOfWeek, $weekdays, true) && ++$count === $meetings) {
                return $date->toDateString();
            }
            $date->addDay();
        }

        return null;
    }
}