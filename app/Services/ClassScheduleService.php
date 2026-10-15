<?php

namespace App\Services;

use App\Models\ClassModel;
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

    /** Lempar error kalau guru sudah mengajar di jam yang sama. */
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

    public function createSchedules(ClassModel $kelas, array $rows): void
    {
        foreach ($rows as $row) {
            $kelas->schedules()->forceCreate([
                'class_id'   => $kelas->id,
                'day'        => $row['day'],
                'start_time' => $row['start_time'],
                'end_time'   => $row['end_time'],
                'room'       => $row['room'] ?? null,
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