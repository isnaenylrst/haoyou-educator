<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\Payment;
use App\Models\ProgressReport;
use App\Models\TeachingJournal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RetentionService
{
    /** Kolom dan nilai yang menandai pertemuan benar-benar terlaksana */
    private const TAUGHT_COLUMN = 'class_status';
    private const TAUGHT_VALUE  = 'Conducted';

    /**
     * Enrollment yang kelasnya sudah berakhir di bulan ini, lengkap dengan status lanjutannya.
     * Atribut tambahan di tiap enrollment:
     *   continuation     none | unpaid | paid
     *   next_enrollment  enrollment lanjutan (yang sudah bayar kalau ada)
     *   paid_total       total pembayaran di enrollment lanjutan
     *   continued        true bila paid
     *   awaiting_payment true bila unpaid
     *
     * @param int|null $teacherId  null = semua guru, 0 = kelas tanpa guru
     */
    private function finishedEnrollments(Carbon $month, ?int $teacherId = null): Collection
    {
        $end = $month->copy()->endOfMonth()->min(today());

        $rows = ClassEnrollment::query()
            ->whereIn('status', ['Active', 'Completed'])
            ->whereHas('class', fn ($q) => $q
                ->whereBetween('end_date', [$month->copy()->startOfMonth(), $end]))
            ->with(['class', 'student'])
            ->get();

        if ($teacherId !== null) {
            $rows = $rows
                ->filter(fn ($e) => (int) ($e->class->teacher_id ?? 0) === $teacherId)
                ->values();
        }

        if ($rows->isEmpty()) {
            return $rows;
        }

        // semua enrollment milik siswa-siswa ini, diambil sekali
        $laterAll = ClassEnrollment::query()
            ->whereIn('student_id', $rows->pluck('student_id')->unique())
            ->where('status', '!=', 'Cancelled')
            ->with(['class', 'programPackage', 'privatePackage'])
            ->get();

        $later = $laterAll->groupBy('student_id');

        // total pembayaran per enrollment (hanya yang amount_paid > 0)
        $paidTotals = Payment::query()
            ->whereIn('enrollment_id', $laterAll->pluck('id'))
            ->where('amount_paid', '>', 0)
            ->selectRaw('enrollment_id, SUM(amount_paid) as total')
            ->groupBy('enrollment_id')
            ->pluck('total', 'enrollment_id');

        return $rows->each(function ($e) use ($later, $paidTotals) {
            $next = ($later[$e->student_id] ?? collect())
                ->filter(fn ($n) => $n->id > $e->id
                    && (is_null($n->class_id) || (int) $n->class_id !== (int) $e->class_id))
                ->values();

            $paidNext = $next->first(fn ($n) => ($paidTotals[$n->id] ?? 0) > 0);

            $e->continuation     = $next->isEmpty() ? 'none' : ($paidNext ? 'paid' : 'unpaid');
            $e->next_enrollment  = $paidNext ?? $next->first();
            $e->paid_total       = $paidNext ? (float) $paidTotals[$paidNext->id] : 0;
            $e->continued        = $e->continuation === 'paid';
            $e->awaiting_payment = $e->continuation === 'unpaid';
        });
    }

    public function monthly(Carbon $month): array
    {
        $rows = $this->finishedEnrollments($month);
        $done = $rows->count();
        $cont = $rows->where('continued', true)->count();

        return [
            'selesai'        => $done,
            'lanjut'         => $cont,
            'menunggu_bayar' => $rows->where('awaiting_payment', true)->count(),
            'rate'           => $done ? round($cont / $done * 100, 1) : null,
        ];
    }

    public function perTeacher(Carbon $month): Collection
    {
        $rows = $this->finishedEnrollments($month)
            ->groupBy(fn ($e) => (int) ($e->class->teacher_id ?? 0));

        $meetings = TeachingJournal::where(self::TAUGHT_COLUMN, self::TAUGHT_VALUE)
            ->whereBetween('session_date', [
                $month->copy()->startOfMonth(), $month->copy()->endOfMonth(),
            ])
            ->selectRaw('teacher_id, COUNT(*) as total')
            ->groupBy('teacher_id')
            ->pluck('total', 'teacher_id');

        $maxLoad = max($meetings->max() ?? 0, 1);

        return $rows->map(function ($group, $teacherId) use ($meetings, $maxLoad) {
            $done = $group->count();
            $cont = $group->where('continued', true)->count();
            $rr   = $done ? $cont / $done * 100 : 0;
            $tl   = ($meetings[$teacherId] ?? 0) / $maxLoad * 100;

            return [
                'teacher_id'     => (int) $teacherId,
                'selesai'        => $done,
                'lanjut'         => $cont,
                'menunggu_bayar' => $group->where('awaiting_payment', true)->count(),
                'rr'             => round($rr, 1),
                'pertemuan'      => $meetings[$teacherId] ?? 0,
                'tl'             => round($tl, 1),
                'score'          => round($rr * 0.7 + $tl * 0.3, 1),
            ];
        })->sortByDesc('score')->values();
    }

    /**
     * Rincian satu guru: kelas-kelasnya yang berakhir di bulan ini, dan status tiap siswa.
     *
     * @param int $teacherId 0 = kelas tanpa guru
     */
    public function teacherDetail(Carbon $month, int $teacherId): array
    {
        $rows = $this->finishedEnrollments($month, $teacherId);

        // status follow up yang dicatat admin di Progress Report (hanya info tambahan)
        $followUps = $rows->isEmpty()
            ? collect()
            : ProgressReport::whereIn('enrollment_id', $rows->pluck('id'))
                ->orderBy('id')
                ->pluck('follow_up_status', 'enrollment_id');

        $order = ['paid' => 0, 'unpaid' => 1, 'none' => 2];

        $classes = $rows->groupBy('class_id')->map(function ($group) use ($followUps, $order) {
            $class = $group->first()->class;

            $students = $group
                ->map(fn ($e) => [
                    'name'        => $e->student->name ?? '-',
                    'state'       => $e->continuation,
                    'next_label'  => $this->nextLabel($e->next_enrollment),
                    'next_status' => $e->next_enrollment->status ?? null,
                    'paid_total'  => $e->paid_total,
                    'follow_up'   => $followUps[$e->id] ?? null,
                ])
                ->sortBy(fn ($s) => $order[$s['state']] . '|' . $s['name'])
                ->values();

            $done = $students->count();
            $paid = $students->where('state', 'paid')->count();

            return [
                'id'             => $class->id,
                'name'           => $class->class_name ?? ('Kelas #' . $class->id),
                'end_date'       => $class->end_date,
                'selesai'        => $done,
                'lanjut'         => $paid,
                'menunggu_bayar' => $students->where('state', 'unpaid')->count(),
                'tidak_lanjut'   => $students->where('state', 'none')->count(),
                'rate'           => $done ? round($paid / $done * 100, 1) : null,
                'students'       => $students,
            ];
        })->sortBy('end_date')->values();

        $done = $classes->sum('selesai');
        $paid = $classes->sum('lanjut');

        return [
            'summary' => [
                'selesai'        => $done,
                'lanjut'         => $paid,
                'menunggu_bayar' => $classes->sum('menunggu_bayar'),
                'tidak_lanjut'   => $classes->sum('tidak_lanjut'),
                'rate'           => $done ? round($paid / $done * 100, 1) : null,
            ],
            'classes' => $classes,
        ];
    }

    private function nextLabel(?ClassEnrollment $next): ?string
    {
        if (! $next) {
            return null;
        }

        return $next->class?->class_name
            ?? $next->programPackage?->package_name
            ?? $next->privatePackage?->package_name
            ?? '-';
    }
}