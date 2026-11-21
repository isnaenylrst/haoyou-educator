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
        $month = $this->month($request);

        $summary  = $service->monthly($month);
        $teachers = $service->perTeacher($month);

        // tren 6 bulan terakhir (termasuk bulan terpilih)
        $trend = collect(range(5, 0))->map(function ($i) use ($service, $month) {
            $m = $month->copy()->subMonths($i);
            return ['label' => $m->translatedFormat('M Y')] + $service->monthly($m);
        });

        $names = Teacher::whereIn('id', $teachers->pluck('teacher_id'))->pluck('name', 'id');

        return view('admin.retention.index', compact('month', 'summary', 'teachers', 'trend', 'names'));
    }

    /** Rincian satu guru. teacherId 0 = kelas yang belum punya guru. */
    public function teacher(Request $request, RetentionService $service, int $teacherId)
    {
        $month = $this->month($request);

        $teacherName = $teacherId === 0
            ? 'Belum ada guru'
            : (Teacher::find($teacherId)?->name ?? abort(404));

        $detail = $service->teacherDetail($month, $teacherId);

        return view('admin.retention.teacher', compact('month', 'teacherId', 'teacherName', 'detail'));
    }

    private function month(Request $request): Carbon
    {
        $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);

        return $request->filled('bulan')
            ? Carbon::createFromFormat('Y-m', $request->bulan)->startOfMonth()
            : now()->startOfMonth();
    }
}