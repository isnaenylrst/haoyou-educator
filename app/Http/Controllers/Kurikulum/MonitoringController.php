<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\ProgressReport;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Services\WeeklyScheduleGridService;
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
        | RANKING: GURU PALING AKTIF MENGAJAR (Poin 7) - Dioptimalkan Bebas N+1
        |--------------------------------------------------------------------------
        */
        $allTeachers = Teacher::with(['classes'])->where('status', 'Active')->get();

        // Eager aggregation untuk mencegah N+1 query
        $journalsSummary = TeachingJournal::query()
            ->whereIn('teacher_id', $allTeachers->pluck('id'))
            ->whereBetween('session_date', [$startDate, $endDate])
            ->selectRaw('teacher_id, count(*) as total, sum(case when class_status = "Conducted" then 1 else 0 end) as conducted')
            ->groupBy('teacher_id')
            ->get()
            ->keyBy('teacher_id');

        $activeRankings = $allTeachers->map(function ($teacher) use ($journalsSummary) {
            $summary = $journalsSummary->get($teacher->id);
            $pertemuanDiajar = (int) ($summary?->conducted ?? 0);
            $totalSesiJadwal = (int) ($summary?->total ?? 0);
            $kelasDiampu     = $teacher->classes->count(); // Menggunakan collection loaded, bukan query ulang

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

        // Total jam mengajar (hanya dari pola aktif)
        $totalHour = 0;
        $schedules = ClassSchedule::current()->get();
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

        // Navigasi minggu untuk jadwal monitoring (seperti Schedule guru)
        try {
            $weekStart = $request->filled('week')
                ? Carbon::parse($request->week)->startOfWeek(Carbon::MONDAY)
                : now()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable $e) {
            $weekStart = now()->startOfWeek(Carbon::MONDAY);
        }
        $weekEnd = $weekStart->copy()->addDays(6);

        // Pola mingguan aktif
        $rows = DB::table('class_schedules as cs')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->leftJoin('teachers as t', 't.id', '=', 'c.teacher_id')
            ->whereIn('c.status', ['Open', 'Running'])
            ->whereNull('cs.effective_until')
            ->select(
                'cs.id', 'cs.class_id', 'cs.day', 'cs.start_time', 'cs.end_time', 'cs.room',
                'c.class_name', 'c.delivery_mode', 'c.teacher_id', 'c.teacher_response', 't.name as teacher_name',
                DB::raw("(select count(*) from class_enrollments ce
                          where ce.class_id = c.id and ce.status = 'Active') as students")
            )
            ->orderBy('cs.start_time')
            ->get();

        // Entri jadwal minggu terpilih: pakai pertemuan nyata (class_sessions),
        // fallback ke class_schedules jika kelas belum ada pertemuannya.
        $weeklySchedules = WeeklyScheduleGridService::weekEntries($rows, $weekStart, $weekEnd, null, 'all', null);

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
            'weekStart',
            'weekEnd',
            'date',
            'teacherId',
            'status'
        ));
    }
}
