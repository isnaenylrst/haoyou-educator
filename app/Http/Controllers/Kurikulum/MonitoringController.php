<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Teacher;
use App\Models\ClassSchedule;
use App\Models\TeachingJournal;
use App\Models\Attendance;
use App\Models\ProgressReport;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        $teacherId = $request->teacher_id;
        $status = $request->status;
        $date = $request->date ?? now()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | DATA GURU
        |--------------------------------------------------------------------------
        */

        $teachers = Teacher::with([
            'classes.programPackage',
            'classes.schedules',
            'teachingJournals.attendances',
            'progressReports',
        ])
        ->when($teacherId, function ($query) use ($teacherId) {
            $query->where('id', $teacherId);
        })
        ->when($status, function ($query) use ($status) {
            $query->where('status', $status);
        })
        ->get();

        /*
        |--------------------------------------------------------------------------
        | TOTAL GURU
        |--------------------------------------------------------------------------
        */

        $totalTeacher = Teacher::count();

        /*
        |--------------------------------------------------------------------------
        | TOTAL SESI MENGAJAR
        |--------------------------------------------------------------------------
        */

        $totalSession = TeachingJournal::count();

        /*
        |--------------------------------------------------------------------------
        | TOTAL JAM MENGAJAR
        |--------------------------------------------------------------------------
        */

        $totalHour = 0;

        $schedules = ClassSchedule::all();

        foreach ($schedules as $schedule) {

            if (
                $schedule->start_time &&
                $schedule->end_time
            ) {

                $start = strtotime($schedule->start_time);
                $end = strtotime($schedule->end_time);

                if ($end > $start) {
                    $totalHour += ($end - $start) / 3600;
                }
            }
        }

        $totalHour = round($totalHour, 1);

        /*
        |--------------------------------------------------------------------------
        | RATA-RATA JAM / GURU
        |--------------------------------------------------------------------------
        */

        $averageHour = $totalTeacher > 0
            ? round($totalHour / $totalTeacher, 1)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | GURU YANG MEMILIKI JURNAL HARI INI
        |--------------------------------------------------------------------------
        */

        $guruHadirHariIni = TeachingJournal::whereHas(
            'attendances',
            function ($query) use ($date) {
                $query->whereDate(
                    'attendance_date',
                    $date
                );
            }
        )
        ->distinct('teacher_id')
        ->count('teacher_id');

        /*
        |--------------------------------------------------------------------------
        | TOTAL ABSENSI HARI INI
        |--------------------------------------------------------------------------
        */

        $totalAttendanceToday = Attendance::whereDate(
            'attendance_date',
            $date
        )->count();

        /*
        |--------------------------------------------------------------------------
        | TOTAL TIDAK HADIR HARI INI
        |--------------------------------------------------------------------------
        */

        $totalTidakHadirHariIni = Attendance::whereDate(
            'attendance_date',
            $date
        )
        ->whereIn('status', [
            'Absent',
            'Sick',
            'Permission'
        ])
        ->count();

        /*
        |--------------------------------------------------------------------------
        | PROGRESS REPORT PENDING
        |--------------------------------------------------------------------------
        */

        $progressReportPending = ProgressReport::whereIn(
            'status',
            [
                'pending',
                'draft'
            ]
        )->count();

        /*
        |--------------------------------------------------------------------------
        | JADWAL MINGGUAN
        |--------------------------------------------------------------------------
        */

        $weeklySchedules = ClassSchedule::with([
            'class.teacher',
            'class.programPackage'
        ])
        ->orderBy('day')
        ->orderBy('start_time')
        ->get();

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'kurikulum.monitoring',
            compact(
                'teachers',
                'totalTeacher',
                'totalSession',
                'totalHour',
                'averageHour',
                'guruHadirHariIni',
                'totalAttendanceToday',
                'totalTidakHadirHariIni',
                'progressReportPending',
                'weeklySchedules',
                'date'
            )
        );
    }
}