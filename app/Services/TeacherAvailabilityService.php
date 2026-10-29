<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\TeacherAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TeacherAvailabilityService
{
    /** Urutkan slot ketersediaan: Senin..Minggu lalu jam mulai. */
    public static function sortSlots($slots)
    {
        return $slots->sortBy(fn ($a) => sprintf(
            '%d-%s',
            (int) array_search($a->day, TeacherAvailability::DAYS, true),
            substr($a->start_time, 0, 5)
        ))->values();
    }

    /** Apakah semua jadwal kelas tertutup oleh slot ketersediaan guru? */
    public static function coversClass($slots, ClassModel $class): bool
    {
        if ($class->schedules->isEmpty()) {
            return false;
        }

        return $class->schedules->every(
            fn ($s) => $slots->contains(fn ($a) => $a->covers($s->day, $s->start_time, $s->end_time))
        );
    }

    /**
     * Bentrok dengan kelas lain milik guru itu (yang sudah diterima)?
     * Mengembalikan nama kelas yang bentrok, atau null.
     */
    public static function conflictWith(int $teacherId, ClassModel $class): ?string
    {
        foreach ($class->schedules as $s) {
            $clash = DB::table('class_schedules as cs')
                ->join('classes as c', 'c.id', '=', 'cs.class_id')
                ->where('c.teacher_id', $teacherId)
                ->where('c.id', '!=', $class->id)
                ->where('c.teacher_response', 'Accepted')
                ->whereIn('c.status', ['Open', 'Running'])
                ->where('cs.day', $s->day)
                ->where('cs.start_time', '<', $s->end_time)
                ->where('cs.end_time', '>', $s->start_time)
                ->value('c.class_name');

            if ($clash) {
                return $clash;
            }
        }

        return null;
    }

    /** Slot baru tidak boleh tumpang tindih dengan slot lain di hari yang sama. */
    public static function overlaps(int $teacherId, string $day, string $start, string $end, ?int $ignoreId = null): bool
    {
        return TeacherAvailability::where('teacher_id', $teacherId)
            ->where('day', $day)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();
    }

    /** Apakah ada slot ketersediaan yang menutup penuh hari & jam ini? */
    public static function slotsCover($slots, string $day, string $start, string $end): bool
    {
        return $slots->contains(fn ($a) => $a->covers($day, $start, $end));
    }

    /**
     * Guru ini sudah punya kelas (yang diterima) di hari & jam tersebut?
     * Mengembalikan nama kelas yang bentrok, atau null.
     */
    public static function busyAt(int $teacherId, string $day, string $start, string $end): ?string
    {
        return DB::table('class_schedules as cs')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->where('c.teacher_id', $teacherId)
            ->where('c.teacher_response', 'Accepted')
            ->whereIn('c.status', ['Open', 'Running'])
            ->where('cs.day', $day)
            ->where('cs.start_time', '<', $end)
            ->where('cs.end_time', '>', $start)
            ->value('c.class_name');
    }

    /**
     * Susun data kalender mingguan ketersediaan semua guru
     * (baris = jam, kolom = hari), dipakai di halaman Kurikulum dan Schedule guru.
     *
     * @param \Illuminate\Support\Collection $slots  TeacherAvailability dengan relasi "teacher" sudah dimuat
     */
    public static function weeklyGrid($slots): array
    {
        $slots = $slots->filter(fn ($a) => $a->teacher !== null)->values();

        // rentang jam default 07:00 - 21:00, melebar otomatis jika ada slot di luar itu
        $startHour = 7;
        $endHour   = 21;

        foreach ($slots as $a) {
            $startHour = min($startHour, (int) substr($a->start_time, 0, 2));

            $h = (int) substr($a->end_time, 0, 2);
            if ((int) substr($a->end_time, 3, 2) > 0) {
                $h++;
            }
            $endHour = max($endHour, $h);
        }

        $startHour = max(0, $startHour);
        $endHour   = min(24, $endHour);
        $hours     = range($startHour, $endHour - 1);

        // kolom Minggu hanya tampil jika ada yang mengisi
        $hasSunday = $slots->contains('day', 'Minggu');
        $days = array_values(array_filter(
            TeacherAvailability::DAYS,
            fn ($d) => $d !== 'Minggu' || $hasSunday
        ));

        $cells    = [];
        $teachers = [];

        foreach ($slots as $a) {
            $s = self::minutes($a->start_time);
            $e = self::minutes($a->end_time);

            if (!isset($teachers[$a->teacher_id])) {
                $teachers[$a->teacher_id] = [
                    'id'    => $a->teacher_id,
                    'name'  => $a->teacher->name,
                    'color' => $a->teacher_id % 8,
                    'slots' => 0,
                ];
            }
            $teachers[$a->teacher_id]['slots']++;

            foreach ($hours as $h) {
                $hs = $h * 60;
                $he = $hs + 60;

                if ($s < $he && $e > $hs) {
                    $cells[$h][$a->day][] = [
                        'teacher_id' => $a->teacher_id,
                        'name'       => $a->teacher->name,
                        'start'      => substr($a->start_time, 0, 5),
                        'end'        => substr($a->end_time, 0, 5),
                        'note'       => $a->note,
                        'partial'    => $s > $hs || $e < $he,
                        'color'      => $a->teacher_id % 8,
                    ];
                }
            }
        }

        foreach ($cells as $h => $byDay) {
            foreach ($byDay as $d => $list) {
                usort($list, fn ($x, $y) => strcmp($x['name'], $y['name']));
                $cells[$h][$d] = $list;
            }
        }

        uasort($teachers, fn ($x, $y) => strcmp($x['name'], $y['name']));

        return [
            'hours'    => $hours,
            'days'     => $days,
            'cells'    => $cells,
            'teachers' => array_values($teachers),
            'total'    => $slots->count(),
        ];
    }

    private static function minutes(string $time): int
    {
        return ((int) substr($time, 0, 2)) * 60 + (int) substr($time, 3, 2);
    }

    /** Memilih ID guru yang cocok dengan seluruh jadwal mingguan kelas. */
    public static function matchingTeacherIds(ClassModel $class): Collection
    {
        $class->loadMissing('schedules');

        if ($class->schedules->isEmpty()) {
            return collect();
        }

        return TeacherAvailability::with('teacher')->get()
            ->filter(fn ($a) => $a->teacher !== null)
            ->groupBy('teacher_id')
            ->filter(fn ($slots, $teacherId) => self::coversClass($slots, $class)
                && self::conflictWith((int) $teacherId, $class) === null)
            ->keys()
            ->values();
    }

    /**
     * Tawarkan ke seluruh guru
     */
    public static function isOfferedTo(int $teacherId, ClassModel $class): bool
    {
        $class->loadMissing('schedules');

        if ($class->schedules->isEmpty()) {
            return false;
        }

        $mine = TeacherAvailability::where('teacher_id', $teacherId)->get();

        if (self::coversClass($mine, $class) && self::conflictWith($teacherId, $class) === null) {
            return true;
        }

        return self::matchingTeacherIds($class)->isEmpty();
    }
}