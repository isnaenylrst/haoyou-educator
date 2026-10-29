<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\TeacherLeave;
use App\Models\TeachingJournal;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Mencari sesi mengajar yang SUDAH lewat tetapi jurnalnya belum diisi.
 *
 * Sesi "seharusnya ada" = baris class_schedules (hari + jam) pada tanggal yang
 * cocok dengan harinya, selama tanggal itu berada di antara start_date dan
 * end_date kelas. Sesi dianggap sudah diisi kalau ada teaching_journals dengan
 * (class_schedule_id, session_date) yang sama.
 */
class MissingJournalFinder
{
    public const DAYS = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
        5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];

    public function find(Carbon $from, Carbon $to, ?int $teacherId = null): Collection
    {
        $now = now();
        $today = $now->copy()->startOfDay();

        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        if ($to->gt($today)) {
            $to = $today->copy();
        }
        if ($from->gt($to)) {
            return collect();
        }

        $schedules = ClassSchedule::query()
            ->with([
                'class.teacher.user',
                'class.level.program',
                'class.level.category',
                'class.programPackage.program',
            ])
            ->whereHas('class', function ($q) use ($from, $to) {
                $q->whereNotNull('teacher_id')
                    ->where('status', '!=', 'Closed')
                    ->whereDate('start_date', '<=', $to->toDateString())
                    ->where(fn ($e) => $e->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $from->toDateString()));
            })
            ->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        $done = TeachingJournal::query()
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->get(['class_schedule_id', 'session_date'])
            ->mapWithKeys(fn ($j) => [$j->class_schedule_id . '|' . $j->session_date->format('Y-m-d') => true]);

        // Cuti yang sudah disetujui: tanpa guru pengganti => sesi tidak dihitung tertunggak,
        // dengan guru pengganti => guru pengganti yang wajib mengisi jurnal.
        $leaves = TeacherLeave::query()
            ->with('replacementTeacher.user')
            ->where('status', 'Approved')
            ->whereBetween('leave_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($l) => $l->class_schedule_id . '|' . $l->leave_date->format('Y-m-d'));

        $byDay = $schedules->groupBy('day');
        $items = collect();

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $dateStr = $date->format('Y-m-d');

            foreach ($byDay->get(self::DAYS[$date->dayOfWeekIso], collect()) as $schedule) {
                $class = $schedule->class;

                if ($class->start_date->gt($date)) {
                    continue;
                }
                if ($class->end_date && $class->end_date->lt($date)) {
                    continue;
                }

                // Sesi hari ini baru dihitung setelah jam selesainya lewat
                if (Carbon::parse($dateStr . ' ' . $schedule->end_time)->gt($now)) {
                    continue;
                }

                $key = $schedule->id . '|' . $dateStr;
                if ($done->has($key)) {
                    continue;
                }

                $responsible = $class->teacher;
                $leave = $leaves->get($key);

                if ($leave) {
                    if (! $leave->replacementTeacher) {
                        continue;
                    }
                    $responsible = $leave->replacementTeacher;
                }

                if (! $responsible || ($teacherId && $responsible->id !== $teacherId)) {
                    continue;
                }

                $items->push((object) [
                    'key'           => $key,
                    'schedule'      => $schedule,
                    'class'         => $class,
                    'teacher'       => $responsible,
                    'is_substitute' => (bool) $leave,
                    'date'          => $date->copy(),
                    'days_late'     => (int) $date->diffInDays($today, true),
                    'reminded_at'   => null,
                ]);
            }
        }

        return $items
            ->sort(fn ($a, $b) => [$b->date->timestamp, $a->schedule->start_time]
                <=> [$a->date->timestamp, $b->schedule->start_time])
            ->values();
    }
}
