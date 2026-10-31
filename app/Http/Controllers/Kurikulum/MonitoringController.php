<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\ProgressReport;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        $teacherId = $request->teacher_id;
        $status    = $request->status;
        $date      = $request->date ?? now()->toDateString();
        $periode   = $request->periode ?? 'bulan'; // minggu | bulan | semester

        // Tentukan rentang tanggal periode untuk ranking keaktifan mengajar
        $startDate = match ($periode) {
            'minggu'   => Carbon::now()->startOfWeek()->toDateString(),
            'semester' => Carbon::now()->subMonths(6)->startOfMonth()->toDateString(),
            default    => Carbon::now()->startOfMonth()->toDateString(),
        };
        $endDate = Carbon::now()->endOfDay()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | RANKING: GURU PALING AKTIF MENGAJAR (Poin 7)
        |--------------------------------------------------------------------------
        | Berdasarkan jumlah pertemuan yang benar-benar diajar (Conducted)
        | dengan filter periode (minggu/bulan/semester).
        */
        $allTeachers = Teacher::with(['classes'])->where('status', 'Active')->get();

        $activeRankings = $allTeachers->map(function ($teacher) use ($startDate, $endDate) {
            // Jumlah pertemuan yang benar-benar diajar
            $pertemuanDiajar = TeachingJournal::where('teacher_id', $teacher->id)
                ->where('class_status', 'Conducted')
                ->whereBetween('session_date', [$startDate, $endDate])
                ->count();

            // Total sesi/jadwal yang seharusnya diajar pada periode ini
            $totalSesiJadwal = TeachingJournal::where('teacher_id', $teacher->id)
                ->whereBetween('session_date', [$startDate, $endDate])
                ->count();

            // Jumlah kelas yang diampu
            $kelasDiampu = $teacher->classes()->count();

            // Persentase kehadiran mengajar
            $denominator = max($totalSesiJadwal, $pertemuanDiajar, 1);
            $persentase = round(($pertemuanDiajar / $denominator) * 100, 1);
            if ($pertemuanDiajar > 0 && $persentase < 1) {
                $persentase = 100.0;
            }

            return (object) [
                'id'                   => $teacher->id,
                'name'                 => $teacher->name,
                'phone'                => $teacher->phone,
                'pertemuan_diajar'     => $pertemuanDiajar,
                'kelas_diampu'         => $kelasDiampu,
                'total_sesi'           => $totalSesiJadwal,
                'persentase_kehadiran' => min($persentase, 100.0),
            ];
        })->sortByDesc('pertemuan_diajar')->values();

        /*
        |--------------------------------------------------------------------------
        | DATA GURU DENGAN FILTER
        |--------------------------------------------------------------------------
        */
        $teachers = Teacher::with([
            'classes.programPackage',
            'classes.schedules',
            'teachingJournals.attendances',
            'progressReports',
        ])
        ->when($teacherId, fn ($q) => $q->where('id', $teacherId))
        ->when($status, fn ($q) => $q->where('status', $status))
        ->paginate(15);

        $totalTeacher = Teacher::count();
        $totalSession = TeachingJournal::where('class_status', 'Conducted')->count();

        // Total jam mengajar
        $totalHour = 0;
        $schedules = ClassSchedule::all();
        foreach ($schedules as $schedule) {
            if ($schedule->start_time && $schedule->end_time) {
                $start = strtotime($schedule->start_time);
                $end   = strtotime($schedule->end_time);
                if ($end > $start) {
                    $totalHour += ($end - $start) / 3600;
                }
            }
        }
        $totalHour = round($totalHour, 1);
        $averageHour = $totalTeacher > 0 ? round($totalHour / $totalTeacher, 1) : 0;

        $guruHadirHariIni = TeachingJournal::whereDate('session_date', $date)
            ->where('class_status', 'Conducted')
            ->distinct('teacher_id')
            ->count('teacher_id');

        $totalAttendanceToday = Attendance::whereDate('attendance_date', $date)->count();

        $totalTidakHadirHariIni = Attendance::whereDate('attendance_date', $date)
            ->whereIn('status', ['Absent', 'Sick', 'Permission'])
            ->count();

        $progressReportPending = ProgressReport::whereIn('status', ['Draft', 'Submitted'])
            ->whereNull('reviewed_at')
            ->count();

        $weeklySchedules = ClassSchedule::with(['class.teacher', 'class.programPackage'])
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        return view('kurikulum.monitoring', compact(
            'teachers',
            'allTeachers',
            'activeRankings',
            'periode',
            'totalTeacher',
            'totalSession',
            'totalHour',
            'averageHour',
            'guruHadirHariIni',
            'totalAttendanceToday',
            'totalTidakHadirHariIni',
            'progressReportPending',
            'weeklySchedules',
            'date',
            'teacherId',
            'status'
        ));
    }
}
