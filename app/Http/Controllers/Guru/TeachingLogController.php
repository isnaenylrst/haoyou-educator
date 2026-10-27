<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeachingLogController extends Controller
{
    public function index(Request $request)
    {
        $teacher = Auth::user()->teacher;
        $type    = $request->get('type', 'all');   // all | Regular | Private | substitute
        $month   = $request->get('month');         // format Y-m

        // cek kolom yang benar-benar ada di database
        $hasCourseType = Schema::hasColumn('program_packages', 'course_type');
        $hasPackageName = Schema::hasColumn('program_packages', 'package_name');
        $hasDuration   = Schema::hasColumn('program_packages', 'duration_minutes');

        if ($hasCourseType) {
            $typeExpr = "pp.course_type";
        } else {
            $nameCheck = "LOWER(c.class_name) LIKE '%private%'";
            if ($hasPackageName) {
                $nameCheck .= " OR LOWER(pp.package_name) LIKE '%private%'";
            }
            $typeExpr = "CASE WHEN {$nameCheck} THEN 'Private' ELSE 'Regular' END";
        }

        $minutesExpr = 'TIME_TO_SEC(TIMEDIFF(cs.end_time, cs.start_time)) / 60';
        $minutesExpr = $hasDuration
            ? "COALESCE({$minutesExpr}, pp.duration_minutes, 0)"
            : "COALESCE({$minutesExpr}, 0)";

        $base = DB::table('teaching_journals as j')
            ->join('classes as c', 'c.id', '=', 'j.class_id')
            ->leftJoin('class_schedules as cs', 'cs.id', '=', 'j.class_schedule_id')
            ->leftJoin('program_packages as pp', 'pp.id', '=', 'c.program_package_id')
            ->leftJoin('materials as m', 'm.id', '=', 'j.material_id')
            ->where(function ($q) use ($teacher) {
                $q->where('j.teacher_id', $teacher->id)
                  ->orWhere('j.substitute_teacher_id', $teacher->id);
            })
            ->select(
                'j.id', 'j.class_status', 'j.status as review_status', 'j.is_substitute',
                'j.learning_activities', 'c.class_name',
                'm.title as material_title',
                DB::raw("({$typeExpr}) as course_type"),
                DB::raw('COALESCE(j.session_date, DATE(j.created_at)) as log_date'),
                DB::raw("({$minutesExpr}) as minutes")
            );

        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
            $base->whereBetween(DB::raw('COALESCE(j.session_date, DATE(j.created_at))'), [$start, $end]);
        }

        // statistik (tidak terpengaruh filter tipe)
        $all = (clone $base)->get();
        $conducted = $all->where('class_status', 'Conducted');

        $stats = [
            'hours'      => round($conducted->sum('minutes') / 60, 1),
            'sessions'   => $conducted->count(),
            'private'    => $conducted->where('course_type', 'Private')->count(),
            'substitute' => $conducted->where('is_substitute', 1)->count(),
        ];

        // tabel (dipengaruhi filter tipe)
        $table = clone $base;
        if ($type === 'substitute') {
            $table->where('j.is_substitute', 1);
        } elseif (in_array($type, ['Regular', 'Private'])) {
            $table->whereRaw("({$typeExpr}) = ?", [$type]);
        }

        $logs = $table->orderByDesc('log_date')->orderByDesc('j.id')
            ->paginate(15)->withQueryString();

        return view('guru.teaching-log', compact('logs', 'stats', 'type', 'month'));
    }
}