<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\RetentionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RetentionController extends Controller
{
    public function index(Request $request, RetentionService $service)
    {
        [$start, $end] = $this->range($request);

        $summary  = $service->monthly($start, $end);
        $teachers = $service->perTeacher($start, $end);

        $trend = $this->trend($service, $start, $end);

        $names = Teacher::whereIn('id', $teachers->pluck('teacher_id'))->pluck('name', 'id');

        $periodLabel = $this->label($start, $end);

        return view('admin.retention.index', compact(
            'start', 'end', 'periodLabel', 'summary', 'teachers', 'trend', 'names'
        ));
    }

    /** Daftar siswa (lanjut / tidak lanjut) beserta program sebelum & sesudah. */
    public function students(Request $request, RetentionService $service)
    {
        $request->validate(['status' => ['nullable', 'in:paid,unpaid,none,expired']]);

        [$start, $end] = $this->range($request);

        $state       = $request->input('status');
        $data        = $service->students($start, $end, $state);
        $periodLabel = $this->label($start, $end);

        return view('admin.retention.students', [
            'start'       => $start,
            'end'         => $end,
            'periodLabel' => $periodLabel,
            'state'       => $state,
            'students'    => $data['rows'],
            'counts'      => $data['counts'],
        ]);
    }

    /** Rincian satu guru. teacherId 0 = kelas yang belum punya guru. */
    public function teacher(Request $request, RetentionService $service, int $teacherId)
    {
        [$start, $end] = $this->range($request);

        $teacherName = $teacherId === 0
            ? 'Belum ada guru'
            : (Teacher::find($teacherId)?->name ?? abort(404));

        $detail      = $service->teacherDetail($start, $end, $teacherId);
        $periodLabel = $this->label($start, $end);

        return view('admin.retention.teacher', compact(
            'start', 'end', 'periodLabel', 'teacherId', 'teacherName', 'detail'
        ));
    }

    /**
     * Tren per bulan mengikuti rentang tanggal.
     * - Bulan di dalam rentang dipotong sesuai tanggal terpilih (jumlahnya = summary).
     * - Minimal 6 baris: kekurangannya diisi bulan-bulan sebelumnya sebagai pembanding (in_range = false).
     * - Maksimal 12 bulan terakhir agar query tidak berat.
     */
    private function trend(RetentionService $service, Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        $last  = $end->copy()->startOfMonth();
        $first = $start->copy()->startOfMonth();

        $first = $first->min($last->copy()->subMonths(5));   // minimal 6 bulan
        $first = $first->max($last->copy()->subMonths(11));  // maksimal 12 bulan

        $rows = collect();

        for ($m = $first->copy(); $m->lte($last); $m->addMonth()) {
            $from = $m->copy()->startOfMonth();
            $to   = $m->copy()->endOfMonth();

            $inRange = $to->gte($start) && $from->lte($end);

            if ($inRange) {
                $from = $from->max($start->copy()->startOfDay());
                $to   = $to->min($end->copy()->endOfDay());
            }

            $isFull  = $from->isSameDay($m->copy()->startOfMonth())
                && $to->isSameDay($m->copy()->endOfMonth());
            $label   = $m->translatedFormat('M Y')
                . (! $isFull ? ' (' . $from->format('d') . '–' . $to->format('d') . ')' : '');

            $rows->push([
                'label'    => $label,
                'in_range' => $inRange,
                'from'     => $from->toDateString(),
                'to'       => $to->toDateString(),
            ] + $service->monthly($from, $to));
        }

        return $rows;
    }

    /** Rentang tanggal: ?dari=Y-m-d&sampai=Y-m-d (fallback ?bulan=Y-m, lalu bulan ini). */
    private function range(Request $request): array
    {
        $request->validate([
            'dari'   => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
            'bulan'  => ['nullable', 'date_format:Y-m'],
        ]);

        if ($request->filled('dari') || $request->filled('sampai')) {
            $start = $request->filled('dari')
                ? Carbon::createFromFormat('Y-m-d', $request->dari)->startOfDay()
                : Carbon::createFromFormat('Y-m-d', $request->sampai)->startOfMonth();

            $end = $request->filled('sampai')
                ? Carbon::createFromFormat('Y-m-d', $request->sampai)->endOfDay()
                : $start->copy()->endOfMonth();

            return [$start, $end];
        }

        $base = $request->filled('bulan')
            ? Carbon::createFromFormat('Y-m', $request->bulan)->startOfMonth()
            : now()->startOfMonth();

        return [$base->copy()->startOfMonth(), $base->copy()->endOfMonth()];
    }

    private function label(Carbon $start, Carbon $end): string
    {
        $isFullMonth = $start->isSameMonth($end)
            && $start->isSameDay($start->copy()->startOfMonth())
            && $end->isSameDay($end->copy()->endOfMonth());

        return $isFullMonth
            ? $start->translatedFormat('F Y')
            : $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y');
    }
}