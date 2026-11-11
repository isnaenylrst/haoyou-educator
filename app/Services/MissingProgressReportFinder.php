<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ClassSession;
use App\Models\ProgressReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MissingProgressReportFinder
{
    public const CYCLE_MONTHS  = 3;
    public const LOOKBACK_DAYS = 120;   // jatuh tempo yang lebih lama dari ini tidak ditampilkan
    private const MAX_CYCLES   = 12;

    /**
     * @param  Carbon|null  $asOf       tanggal acuan (default hari ini); berguna untuk uji coba
     * @param  int|null     $teacherId  batasi ke satu laoshi (guru kelas)
     */
    public function find(?Carbon $asOf = null, ?int $teacherId = null): Collection
    {
        $today  = ($asOf ?? now())->copy()->startOfDay();
        $oldest = $today->copy()->subDays(self::LOOKBACK_DAYS);

        $enrollments = ClassEnrollment::query()
            ->with(['student:id,name', 'class.teacher', 'class.level', 'class.programPackage.program'])
            ->whereIn('status', ['Active', 'Completed'])
            ->whereHas('class', fn ($q) => $q
                ->whereNotNull('teacher_id')
                ->where('teacher_response', 'Accepted')
                ->where('status', '!=', 'Closed')
                ->when($teacherId, fn ($w) => $w->where('teacher_id', $teacherId)))
            ->get();

        if ($enrollments->isEmpty()) {
            return collect();
        }

        $lastSession = ClassSession::query()
            ->where('kind', 'Regular')
            ->where('status', '!=', 'Cancelled')
            ->whereIn('class_id', $enrollments->pluck('class_id')->unique()->values())
            ->selectRaw('class_id, max(session_date) as last_date')
            ->groupBy('class_id')
            ->pluck('last_date', 'class_id');

        $reports = ProgressReport::query()
            ->whereIn('enrollment_id', $enrollments->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('enrollment_id');

        $items = collect();

        foreach ($enrollments as $e) {
            $class = $e->class;

            if (! $class || ! $class->teacher) {
                continue;
            }

            $classEnd = isset($lastSession[$class->id])
                ? Carbon::parse($lastSession[$class->id])->startOfDay()
                : ($class->end_date ? Carbon::parse($class->end_date)->startOfDay() : null);

            if (! $classEnd) {
                continue;
            }

            $joined     = Carbon::parse($e->enrollment_date ?: $class->start_date)->startOfDay();
            $classStart = $class->start_date ? Carbon::parse($class->start_date)->startOfDay() : null;
            $start      = ($classStart && $classStart->gt($joined)) ? $classStart : $joined;

            // Siklus: tiap 3 bulan sejak masuk; siklus terakhir berakhir di pertemuan terakhir kelas
            $cycles = [];
            for ($n = 1; $n <= self::MAX_CYCLES; $n++) {
                $boundary = $start->copy()->addMonthsNoOverflow(self::CYCLE_MONTHS * $n);
                $isFinal  = $boundary->gte($classEnd);

                $cycles[] = [
                    'n'     => $n,
                    'due'   => $isFinal ? $classEnd->copy() : $boundary,
                    'final' => $isFinal,
                ];

                if ($isFinal) {
                    break;
                }
            }

            $mine = $reports->get($e->id, collect())->values();

            foreach ($cycles as $i => $cycle) {
                $due = $cycle['due'];

                if ($due->gt($today)) {
                    break; // belum jatuh tempo, siklus berikutnya pasti lebih lambat
                }

                if ($due->lt($oldest)) {
                    continue; // terlalu lama
                }

                $report = $mine->get($i);

                if ($report && $report->status === 'Submitted') {
                    continue; // terpenuhi
                }

                $state = ! $report
                    ? 'Belum Dibuat'
                    : ($report->returned_at ? 'Dikembalikan' : 'Draft');

                $label = $cycle['final']
                    ? 'Pertemuan terakhir kelas' . ($cycle['n'] === 1 ? ' (kelas kurang dari 3 bulan)' : '')
                    : "Siklus {$cycle['n']} (3 bulan sejak masuk kelas)";

                $items->push((object) [
                    'key'        => $e->id . '|' . $cycle['n'],
                    'enrollment' => $e,
                    'class'      => $class,
                    'student'    => $e->student,
                    'teacher'    => $class->teacher,
                    'cycle'      => $cycle['n'],
                    'is_final'   => $cycle['final'],
                    'kind_label' => $label,
                    'due'        => $due->copy(),
                    'report'     => $report,
                    'state'      => $state,
                    'days_late'  => (int) $due->diffInDays($today, true),
                    'overdue'    => $today->gt($due->copy()->addDays(ProgressReportService::GRACE_DAYS)),
                    'reminded_at' => null,
                ]);
            }
        }

        return $items
            ->sortByDesc(fn ($i) => $i->days_late)
            ->values();
    }
}