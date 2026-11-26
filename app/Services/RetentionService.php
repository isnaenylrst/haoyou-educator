<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\Payment;
use App\Models\ProgressReport;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RetentionService
{
    /** Kolom dan nilai yang menandai pertemuan benar-benar terlaksana */
    private const TAUGHT_COLUMN = 'class_status';
    private const TAUGHT_VALUE = 'Conducted';

    /**
     * Enrollment yang kelasnya berakhir dalam rentang tanggal.
     *
     * @param int|null $teacherId null = semua guru, 0 = kelas tanpa guru
     */
    private function finishedEnrollments(
        Carbon $start,
        Carbon $end,
        ?int $teacherId = null
    ): Collection {
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->endOfDay()->min(now());

        return $this->finishedEnrollmentsBetween($from, $to, $teacherId);
    }

    /**
     * Enrollment untuk rentang tanggal bebas.
     * Digunakan oleh Monitoring Kurikulum.
     */
    private function finishedEnrollmentsBetween(
        Carbon $start,
        Carbon $end,
        ?int $teacherId = null
    ): Collection {
        $rows = ClassEnrollment::query()
            ->whereIn('status', ['Active', 'Completed'])
            ->whereHas('class', fn ($q) => $q->whereBetween('end_date', [
                $start->copy()->startOfDay(),
                $end,
            ]))
            ->with([
                'class.teacher',
                'student',
                'programPackage',
                'privatePackage',
            ])
            ->get();

        if ($teacherId !== null) {
            $rows = $rows
                ->filter(fn ($e) => (int) ($e->class->teacher_id ?? 0) === $teacherId)
                ->values();
        }

        if ($rows->isEmpty()) {
            return $rows;
        }

        // Semua enrollment milik siswa-siswa ini diambil sekaligus.
        $laterAll = ClassEnrollment::query()
            ->whereIn('student_id', $rows->pluck('student_id')->unique())
            ->where('status', '!=', 'Cancelled')
            ->with([
                'class',
                'programPackage',
                'privatePackage',
            ])
            ->get();

        $later = $laterAll->groupBy('student_id');

        // Total dan tanggal pembayaran pertama per enrollment.
        $payRows = Payment::query()
            ->whereIn('enrollment_id', $laterAll->pluck('id'))
            ->where('amount_paid', '>', 0)
            ->selectRaw('
                enrollment_id,
                SUM(amount_paid) as total,
                MIN(payment_date) as first_date
            ')
            ->groupBy('enrollment_id')
            ->get();

        $paidTotals = $payRows->pluck('total', 'enrollment_id');
        $paidDates = $payRows->pluck('first_date', 'enrollment_id');

        $today = today();

        return $rows->each(function ($e) use (
            $later,
            $paidTotals,
            $paidDates,
            $today
        ) {
            // Batas lanjut: satu bulan setelah kelas berakhir.
            $deadline = Carbon::parse($e->class->end_date)
                ->addMonth()
                ->startOfDay();

            $lapsed = $today->gt($deadline);

            $next = ($later[$e->student_id] ?? collect())
                ->filter(fn ($n) => $n->id > $e->id
                    && (
                        is_null($n->class_id)
                        || (int) $n->class_id !== (int) $e->class_id
                    ))
                ->values();

            // Enrollment lanjutan harus dibuat dalam batas satu bulan.
            $inWindow = $next
                ->filter(fn ($n) => $n->enrollment_date
                    && Carbon::parse($n->enrollment_date)
                        ->startOfDay()
                        ->lte($deadline))
                ->values();

            $paidNext = $inWindow->first(
                fn ($n) => ($paidTotals[$n->id] ?? 0) > 0
            );

            if ($paidNext) {
                $state = 'paid';
                $shown = $paidNext;
            } elseif ($inWindow->isNotEmpty()) {
                $state = $lapsed ? 'expired' : 'unpaid';
                $shown = $inWindow->first();
            } else {
                $state = $lapsed ? 'expired' : 'none';
                $shown = $next->first();
            }

            $e->continuation = $state;
            $e->next_enrollment = $shown;

            $e->paid_total = ($state === 'paid' && $paidNext)
                ? (float) $paidTotals[$paidNext->id]
                : 0;

            $e->paid_date = ($state === 'paid' && $paidNext)
                ? ($paidDates[$paidNext->id] ?? null)
                : null;

            $e->continued = $state === 'paid';
            $e->awaiting_payment = $state === 'unpaid';
            $e->expired = $state === 'expired';
        });
    }

    /**
     * Ringkasan retention bulanan atau rentang tanggal.
     */
    public function monthly(Carbon $start, ?Carbon $end = null): array
    {
        $end = $end ?? $start->copy()->endOfMonth();

        $rows = $this->finishedEnrollments($start, $end);

        return $this->summarize($rows);
    }

    /**
     * Ranking guru.
     *
     * Mendukung pemanggilan dengan:
     * perTeacher($month)
     * perTeacher($start, $end)
     */
    public function perTeacher(
        Carbon $start,
        ?Carbon $end = null
    ): Collection {
        $end = $end ?? $start->copy()->endOfMonth();

        $rows = $this->finishedEnrollments($start, $end);

        return $this->rankTeachers(
            $rows,
            $start->copy()->startOfDay(),
            $end->copy()->endOfDay()->min(now())
        );
    }

    /**
     * Laporan retention untuk rentang tanggal bebas.
     * Dipakai Monitoring Kurikulum agar perhitungan sama
     * dengan menu Admin -> Retention.
     *
     * @return array{summary: array, teachers: Collection, classes: Collection}
     */
    public function report(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay()->min(now());

        $rows = $this->finishedEnrollmentsBetween($from, $to);

        return [
            'summary' => $this->summarize($rows),
            'teachers' => $this->rankTeachers($rows, $from, $to),
            'classes' => $this->perClass($rows),
        ];
    }

    /**
     * Menghitung ringkasan retention.
     */
    private function summarize(Collection $rows): array
    {
        $done = $rows->count();
        $cont = $rows->where('continued', true)->count();

        return [
            'selesai' => $done,
            'lanjut' => $cont,
            'menunggu_bayar' => $rows->where('awaiting_payment', true)->count(),
            'expired' => $rows->where('expired', true)->count(),
            'belum_lanjut' => $rows->where('continuation', 'none')->count(),
            'rate' => $done ? round($cont / $done * 100, 1) : null,
        ];
    }

    /**
     * Daftar siswa yang kelasnya berakhir dalam rentang tanggal.
     *
     * @param string|null $state paid | unpaid | none | expired | null
     * @return array{rows: Collection, counts: array}
     */
    public function students(
        Carbon $start,
        Carbon $end,
        ?string $state = null
    ): array {
        $all = $this->finishedEnrollments($start, $end);

        $counts = [
            'all' => $all->count(),
            'paid' => $all->where('continuation', 'paid')->count(),
            'unpaid' => $all->where('continuation', 'unpaid')->count(),
            'none' => $all->where('continuation', 'none')->count(),
            'expired' => $all->where('continuation', 'expired')->count(),
        ];

        $rows = in_array($state, ['paid', 'unpaid', 'none', 'expired'], true)
            ? $all->where('continuation', $state)
            : $all;

        $teachers = Teacher::whereIn(
            'id',
            $rows->pluck('class.teacher_id')->filter()->unique()
        )->pluck('name', 'id');

        $order = [
            'paid' => 0,
            'unpaid' => 1,
            'none' => 2,
            'expired' => 3,
        ];

        $list = $rows->map(function ($e) use ($teachers) {
            $next = $e->next_enrollment;

            return [
                'name' => $e->student->name ?? '-',
                'teacher' => $teachers[$e->class->teacher_id ?? 0] ?? null,
                'state' => $e->continuation,
                'prev_program' => $this->programLabel($e),
                'prev_class' => $e->class->class_name ?? null,
                'prev_start' => $this->date($e->class->start_date ?? null),
                'prev_end' => $this->date($e->class->end_date ?? null),
                'next_program' => $next ? $this->programLabel($next) : null,
                'next_class' => $next?->class?->class_name,
                'next_date' => $this->date($next?->enrollment_date),
                'next_status' => $next->status ?? null,
                'paid_total' => $e->paid_total,
                'paid_date' => $this->date($e->paid_date),
            ];
        })
            ->sortBy(fn ($r) => $order[$r['state']]
                . '|'
                . ($r['prev_end']?->format('Ymd') ?? '')
                . '|'
                . $r['name'])
            ->values();

        return [
            'rows' => $list,
            'counts' => $counts,
        ];
    }

    /**
     * Skor guru:
     * 70% retention rate + 30% beban mengajar.
     */
    private function rankTeachers(
        Collection $rows,
        Carbon $meetFrom,
        Carbon $meetTo
    ): Collection {
        $grouped = $rows->groupBy(
            fn ($e) => (int) ($e->class->teacher_id ?? 0)
        );

        $meetings = TeachingJournal::where(
            self::TAUGHT_COLUMN,
            self::TAUGHT_VALUE
        )
            ->whereBetween('session_date', [$meetFrom, $meetTo])
            ->selectRaw('teacher_id, COUNT(*) as total')
            ->groupBy('teacher_id')
            ->pluck('total', 'teacher_id');

        $maxLoad = max($meetings->max() ?? 0, 1);

        return $grouped->map(function ($group, $teacherId) use (
            $meetings,
            $maxLoad
        ) {
            $done = $group->count();
            $cont = $group->where('continued', true)->count();

            $rr = $done ? $cont / $done * 100 : 0;
            $tl = ($meetings[$teacherId] ?? 0) / $maxLoad * 100;

            return [
                'teacher_id' => (int) $teacherId,
                'teacher_name' => $group->first()->class?->teacher?->name
                    ?? 'Belum ada guru',
                'selesai' => $done,
                'lanjut' => $cont,
                'menunggu_bayar' => $group
                    ->where('awaiting_payment', true)
                    ->count(),
                'rr' => round($rr, 1),
                'pertemuan' => $meetings[$teacherId] ?? 0,
                'tl' => round($tl, 1),
                'score' => round($rr * 0.7 + $tl * 0.3, 1),
                'continued_students' => $group
                    ->where('continued', true)
                    ->pluck('student')
                    ->filter()
                    ->unique('id')
                    ->values(),
            ];
        })->sortByDesc('score')->values();
    }

    /**
     * Ringkasan retention per kelas.
     */
    private function perClass(Collection $rows): Collection
    {
        return $rows->groupBy('class_id')->map(function ($group) {
            $class = $group->first()->class;

            $done = $group->count();
            $cont = $group->where('continued', true)->count();

            return [
                'class_id' => (int) $class->id,
                'class_name' => $class->class_name
                    ?? ('Kelas #' . $class->id),
                'teacher_name' => $class->teacher?->name
                    ?? 'Belum ada guru',
                'end_date' => $class->end_date,
                'selesai' => $done,
                'lanjut' => $cont,
                'menunggu_bayar' => $group
                    ->where('awaiting_payment', true)
                    ->count(),
                'expired' => $group->where('expired', true)->count(),
                'rate' => $done
                    ? round($cont / $done * 100, 1)
                    : null,
            ];
        })->sortByDesc('end_date')->values();
    }

    /**
     * Rincian satu guru berdasarkan kelas dan status tiap siswa.
     *
     * @param int $teacherId 0 = kelas tanpa guru
     */
    public function teacherDetail(
        Carbon $start,
        Carbon $end,
        int $teacherId
    ): array {
        $rows = $this->finishedEnrollments($start, $end, $teacherId);

        // Status follow up dari Progress Report.
        $followUps = $rows->isEmpty()
            ? collect()
            : ProgressReport::whereIn('enrollment_id', $rows->pluck('id'))
                ->orderBy('id')
                ->pluck('follow_up_status', 'enrollment_id');

        $order = [
            'paid' => 0,
            'unpaid' => 1,
            'none' => 2,
            'expired' => 3,
        ];

        $classes = $rows->groupBy('class_id')->map(
            function ($group) use ($followUps, $order) {
                $class = $group->first()->class;

                $students = $group
                    ->map(fn ($e) => [
                        'name' => $e->student->name ?? '-',
                        'state' => $e->continuation,
                        'next_label' => $this->nextLabel($e->next_enrollment),
                        'next_status' => $e->next_enrollment->status ?? null,
                        'paid_total' => $e->paid_total,
                        'follow_up' => $followUps[$e->id] ?? null,
                    ])
                    ->sortBy(fn ($s) => $order[$s['state']] . '|' . $s['name'])
                    ->values();

                $done = $students->count();
                $paid = $students->where('state', 'paid')->count();

                return [
                    'id' => $class->id,
                    'name' => $class->class_name
                        ?? ('Kelas #' . $class->id),
                    'end_date' => $class->end_date,
                    'selesai' => $done,
                    'lanjut' => $paid,
                    'menunggu_bayar' => $students
                        ->where('state', 'unpaid')
                        ->count(),
                    'tidak_lanjut' => $students
                        ->where('state', 'none')
                        ->count(),
                    'expired' => $students
                        ->where('state', 'expired')
                        ->count(),
                    'rate' => $done
                        ? round($paid / $done * 100, 1)
                        : null,
                    'students' => $students,
                ];
            }
        )->sortBy('end_date')->values();

        $done = $classes->sum('selesai');
        $paid = $classes->sum('lanjut');

        return [
            'summary' => [
                'selesai' => $done,
                'lanjut' => $paid,
                'menunggu_bayar' => $classes->sum('menunggu_bayar'),
                'tidak_lanjut' => $classes->sum('tidak_lanjut'),
                'expired' => $classes->sum('expired'),
                'rate' => $done
                    ? round($paid / $done * 100, 1)
                    : null,
            ],
            'classes' => $classes,
        ];
    }

    /**
     * Label program dari enrollment.
     */
    private function programLabel(ClassEnrollment $e): string
    {
        return $e->programPackage?->package_name
            ?? $e->privatePackage?->package_name
            ?? $e->class?->class_name
            ?? '-';
    }

    /**
     * Mengubah tanggal menjadi Carbon.
     */
    private function date($value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }

    /**
     * Label enrollment lanjutan.
     */
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
