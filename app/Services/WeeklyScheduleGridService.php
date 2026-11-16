<?php

namespace App\Services;

use App\Models\ClassSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WeeklyScheduleGridService
{
    public const DAYS = [
        'Senin' => 0, 'Selasa' => 1, 'Rabu' => 2, 'Kamis' => 3,
        'Jumat' => 4, 'Sabtu' => 5, 'Minggu' => 6,
    ];

    /**
     * Skema warna kartu jadwal per jenis kelas.
     */
    public static function colorFor(string $className): string
    {
        $n = strtolower($className);

        return match (true) {
            str_contains($n, 'maochong') => 'orange',
            str_contains($n, 'jianer'), str_contains($n, 'jian er') => 'blue',
            str_contains($n, 'hudie')    => 'purple',
            str_contains($n, 'feixiang') => 'red',
            default                      => 'green', // HSK / Private / NSP
        };
    }

    /**
     * Mengambil entri grid untuk satu minggu.
     * - Pertemuan nyata (class_sessions) Regular pada minggu yang dipilih dipakai sebagai sumber utama.
     *   Sesi yang dipindah (reschedule) akan tampil di hari/jam barunya dengan catatan moved_note.
     * - Kelas tanpa pertemuan class_sessions pada minggu yang dipilih tetap jatuh ke pola class_schedules
     *   aktif (current) sebagai fallback, tanpa duplikat.
     */
    public static function weekEntries(
        $rows,
        Carbon $weekStart,
        Carbon $weekEnd,
        ?int $myId = null,
        string $scope = 'all',
        ?int $teacherFilterId = null
    ): Collection {
        $classIds     = $rows->pluck('class_id')->unique()->values()->all();
        $classInfo    = $rows->groupBy('class_id')->map->first();
        $teacherNames = DB::table('teachers')->pluck('name', 'id');

        $entries = collect();

        // 1) Sesi pertemuan minggu ini (Cancelled disembunyikan)
        $sessions = ClassSession::whereIn('class_id', $classIds)
            ->where('kind', 'Regular')
            ->whereIn('status', ['Scheduled', 'Completed'])
            ->whereBetween('session_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('start_time')
            ->get();

        // Kelas yang sudah punya sesi pada minggu ini (berdasarkan session_date)
        $classesWithSessionsThisWeek = $sessions->pluck('class_id')->unique()->flip();

        // Cari juga sesi yang aslinya minggu ini tetapi dipindahkan keluar ke minggu lain
        $movedOutThisWeek = ClassSession::whereIn('class_id', $classIds)
            ->where('kind', 'Regular')
            ->where('status', '!=', 'Cancelled')
            ->whereBetween('original_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where(function ($q) use ($weekStart, $weekEnd) {
                $q->where('session_date', '<', $weekStart->toDateString())
                  ->orWhere('session_date', '>', $weekEnd->toDateString());
            })
            ->pluck('class_id')
            ->unique()
            ->flip();

        // 2) Kelas tanpa data class_sessions pada minggu ini tetap jatuh ke pola class_schedules (fallback)
        foreach ($rows as $r) {
            // Jika kelas sudah punya pertemuan di minggu ini atau sesi aslinya dipindah ke luar minggu ini, jangan fallback
            if ($classesWithSessionsThisWeek->has($r->class_id) || $movedOutThisWeek->has($r->class_id) || ! isset(self::DAYS[$r->day])) {
                continue;
            }

            $date = $weekStart->copy()->addDays(self::DAYS[$r->day])->toDateString();
            $entries->push(self::entry(
                $r,
                $r->id,
                $date,
                $r->start_time,
                $r->end_time,
                $r->room,
                $r->teacher_id,
                $r->teacher_name,
                $myId
            ));
        }

        // Masukkan semua sesi pertemuan nyata minggu ini
        foreach ($sessions as $s) {
            $info = $classInfo->get($s->class_id);
            if (! $info) {
                continue;
            }

            $tid = $s->teacher_id ?? $info->teacher_id;

            // Filter scope guru
            if ($scope === 'mine' && $myId !== null && (int) $tid !== $myId) {
                // Kecuali guru adalah pengganti yang disetujui
                $isRep = DB::table('teacher_leaves')
                    ->where('replacement_teacher_id', $myId)
                    ->where('status', 'Approved')
                    ->whereDate('leave_date', $s->session_date->toDateString())
                    ->where(function ($q) use ($s) {
                        if ($s->class_schedule_id) {
                            $q->where('class_schedule_id', $s->class_schedule_id);
                        }
                    })
                    ->exists();

                if (! $isRep) {
                    continue;
                }
            } elseif ($teacherFilterId && (int) $tid !== (int) $teacherFilterId) {
                continue;
            }

            $e = self::entry(
                $info,
                $s->class_schedule_id ?? $info->id,
                $s->session_date->toDateString(),
                $s->start_time,
                $s->end_time,
                $s->location_type === 'Home Visit' ? 'Home Visit' : $s->room,
                $tid,
                $teacherNames[$tid] ?? null,
                $myId
            );

            // Penanda kalau pertemuan ini pernah dipindah admin
            $e->moved_note = null;
            if ($s->original_date) {
                $e->moved_note = $s->original_date->isSameDay($s->session_date)
                    ? 'Jam/ruang diubah'
                    : 'Dipindah dari ' . $s->original_date->locale('id')->translatedFormat('D, d M');
            }

            $entries->push($e);
        }

        return $entries->values();
    }

    /**
     * Membentuk objek satu kartu di grid kalender jadwal.
     */
    public static function entry(
        object $info,
        $scheduleId,
        string $date,
        string $start,
        string $end,
        ?string $room,
        $teacherId,
        ?string $teacherName,
        ?int $myId = null
    ): object {
        $e = clone $info;

        $e->id           = $scheduleId;
        $e->day          = array_search(Carbon::parse($date)->dayOfWeekIso - 1, self::DAYS, true);
        $e->start_time   = $start;
        $e->end_time     = $end;
        $e->room         = $room;
        $e->teacher_id   = $teacherId;
        $e->teacher_name = $teacherName;
        $e->color        = self::colorFor($info->class_name);
        $e->is_mine      = $myId !== null && (int) $teacherId === $myId && ($info->teacher_response ?? null) === 'Accepted';
        $e->date         = $date;
        $e->time         = substr($start, 0, 5) . ' – ' . substr($end, 0, 5);
        $e->is_filled    = false;
        $e->moved_note   = null;

        return $e;
    }
}
