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
        | RETENTION RATE & RANKING RETENSI GURU (KURIKULUM 5)
        |--------------------------------------------------------------------------
        | Siswa selesai: siswa di kelas yang statusnya Selesai/Completed/Closed ATAU end_date sudah lewat.
        | Siswa lanjut: siswa selesai yang terdaftar di kelas baru/paket lanjutan setelah end_date kelas tersebut.
        */
        $todayStr = now()->toDateString();

        // 1. Ambil semua kelas yang telah berakhir (status selesai atau end_date < hari ini)
        $endedClasses = \App\Models\ClassModel::with(['teacher', 'enrollments.student'])
            ->where(function ($q) use ($todayStr) {
                $q->whereIn('status', ['Selesai', 'Completed', 'Closed', 'Finished'])
                  ->orWhere(function ($q2) use ($todayStr) {
                      $q2->whereNotNull('end_date')
                         ->where('end_date', '<', $todayStr);
                  });
            })
            ->when($periode === 'minggu', fn ($q) => $q->where('end_date', '>=', $startDate))
            ->when($periode === 'bulan', fn ($q) => $q->where('end_date', '>=', $startDate))
            ->when($periode === 'semester', fn ($q) => $q->where('end_date', '>=', $startDate))
            ->get();

        // 2. Ambil semua pendaftaran aktif / valid dari seluruh siswa
        $allEnrollments = DB::table('class_enrollments as ce')
            ->join('classes as c', 'c.id', '=', 'ce.class_id')
            ->whereNull('ce.deleted_at')
            ->where('ce.status', '!=', 'Cancelled')
            ->select('ce.id', 'ce.student_id', 'ce.class_id', 'ce.enrollment_date', 'ce.created_at', 'c.start_date', 'c.end_date')
            ->get();

        $enrollmentsByStudent = $allEnrollments->groupBy('student_id');

        // Evaluasi per kelas
        $classRetentionList = collect();
        $teacherRetentionMap = [];

        foreach ($endedClasses as $cls) {
            $teacherId = $cls->teacher_id;
            $teacherName = $cls->teacher?->name ?? 'Belum ada guru';
            $clsEndDate = $cls->end_date ? Carbon::parse($cls->end_date)->startOfDay() : null;

            // Siswa di kelas yang berakhir
            $classStudents = $cls->enrollments->pluck('student')->filter()->unique('id');
            $totalFinishedStudents = $classStudents->count();
            $continuedStudentsList = collect();

            foreach ($classStudents as $student) {
                $studentAllEnr = $enrollmentsByStudent->get($student->id, collect());
                // Siswa dianggap lanjut jika memiliki enrollment lain dengan enrollment_date / start_date > end_date kelas ini (atau class_id berbeda yang dibuat setelahnya)
                $isContinued = $studentAllEnr->contains(function ($other) use ($cls, $clsEndDate) {
                    if ($other->class_id == $cls->id) {
                        return false;
                    }
                    if ($clsEndDate) {
                        $otherDate = $other->enrollment_date ?: ($other->start_date ?: $other->created_at);
                        return $otherDate && Carbon::parse($otherDate)->startOfDay()->gte($clsEndDate);
                    }
                    return $other->id > $cls->id;
                });

                if ($isContinued) {
                    $continuedStudentsList->push($student);
                }
            }

            $continuedCount = $continuedStudentsList->count();
            $ratePercent = $totalFinishedStudents > 0
                ? round(($continuedCount / $totalFinishedStudents) * 100, 1)
                : null;

            $classRetentionList->push((object) [
                'class_id'           => $cls->id,
                'class_name'         => $cls->class_name,
                'teacher_name'       => $teacherName,
                'end_date'           => $cls->end_date,
                'finished_count'     => $totalFinishedStudents,
                'continued_count'    => $continuedCount,
                'rate_percent'       => $ratePercent,
                'rate_label'         => $ratePercent !== null ? "{$ratePercent}%" : '-',
                'continued_students' => $continuedStudentsList,
            ]);

            if ($teacherId) {
                if (!isset($teacherRetentionMap[$teacherId])) {
                    $teacherRetentionMap[$teacherId] = [
                        'teacher_id'         => $teacherId,
                        'teacher_name'       => $teacherName,
                        'finished_count'     => 0,
                        'continued_count'    => 0,
                        'continued_students' => collect(),
                    ];
                }
                $teacherRetentionMap[$teacherId]['finished_count'] += $totalFinishedStudents;
                $teacherRetentionMap[$teacherId]['continued_count'] += $continuedCount;
                foreach ($continuedStudentsList as $st) {
                    if (!$teacherRetentionMap[$teacherId]['continued_students']->contains('id', $st->id)) {
                        $teacherRetentionMap[$teacherId]['continued_students']->push($st);
                    }
                }
            }
        }

        // Hitung ranking guru berdasarkan retention
        $teacherRetentionRankings = collect($teacherRetentionMap)->map(function ($row) {
            $finished = $row['finished_count'];
            $continued = $row['continued_count'];
            $percent = $finished > 0 ? round(($continued / $finished) * 100, 1) : null;

            return (object) [
                'teacher_id'         => $row['teacher_id'],
                'teacher_name'       => $row['teacher_name'],
                'finished_count'     => $finished,
                'continued_count'    => $continued,
                'retention_percent'  => $percent,
                'retention_label'    => $percent !== null ? "{$percent}%" : '-',
                'continued_students' => $row['continued_students'],
            ];
        })->sort(function ($a, $b) {
            // Urutkan berdasarkan siswa lanjut terbanyak, lalu persentase
            if ($a->continued_count === $b->continued_count) {
                return ($b->retention_percent ?? 0) <=> ($a->retention_percent ?? 0);
            }
            return $b->continued_count <=> $a->continued_count;
        })->values();

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
            'classRetentionList',
            'teacherRetentionRankings',
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
