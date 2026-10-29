<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\TeacherNotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    private function colorFor(string $name): string
    {
        $n = strtolower($name);
        return match (true) {
            str_contains($n, 'maochong') => '#F2A12E',
            str_contains($n, 'jianer'), str_contains($n, 'jian er') => '#3B82F6',
            str_contains($n, 'hudie')    => '#7C4DFF',
            str_contains($n, 'feixiang') => '#D9534F',
            default                      => '#28A745',
        };
    }

    public function index()
    {
        $teacher = Auth::user()->teacher; // dijamin ada oleh middleware teacher.profile
        $now     = now();
        $today   = $now->toDateString();

        $hasTotal   = Schema::hasColumn('program_packages', 'total_meetings');
        $enrollDate = Schema::hasColumn('class_enrollments', 'enrollment_date') ? 'enrollment_date' : 'created_at';

        // ---------- KELAS AKTIF + PROGRESS ----------
        $classes = DB::table('classes as c')
            ->leftJoin('program_packages as pp', 'pp.id', '=', 'c.program_package_id')
            ->where('c.teacher_id', $teacher->id)
            ->where('c.teacher_response', 'Accepted')
            ->whereIn('c.status', ['Open', 'Running'])
            ->select(
                'c.id', 'c.class_name', 'c.delivery_mode',
                DB::raw($hasTotal ? 'pp.total_meetings as total_meetings' : 'NULL as total_meetings'),
                DB::raw("(select count(*) from class_enrollments ce where ce.class_id = c.id and ce.status = 'Active') as students"),
                DB::raw("(select count(*) from teaching_journals j where j.class_id = c.id and j.class_status = 'Conducted') as done")
            )
            ->orderBy('c.class_name')->get()
            ->map(function ($c) {
                $total = (int) $c->total_meetings;
                $c->pct    = $total > 0 ? min(100, (int) round($c->done / $total * 100)) : 0;
                $c->color  = $this->colorFor($c->class_name);
                $c->state  = ($total > 0 && $c->done >= $total) ? 'Completed' : ($c->done > 0 ? 'Ongoing' : 'Scheduled');
                return $c;
            });

        // ---------- STATISTIK ----------
        $enrollQuery = DB::table('class_enrollments as ce')
            ->join('classes as c', 'c.id', '=', 'ce.class_id')
            ->where('c.teacher_id', $teacher->id)
            ->where('c.teacher_response', 'Accepted')
            ->where('ce.status', 'Active');

        $totalStudents = (clone $enrollQuery)->distinct()->count('ce.student_id');
        $newStudents   = (clone $enrollQuery)
            ->whereMonth("ce.$enrollDate", $now->month)->whereYear("ce.$enrollDate", $now->year)
            ->distinct()->count('ce.student_id');

        $sessionsMonth = DB::table('teaching_journals')
            ->where('teacher_id', $teacher->id)->where('class_status', 'Conducted')
            ->whereRaw('YEAR(COALESCE(session_date, DATE(created_at))) = ?', [$now->year])
            ->whereRaw('MONTH(COALESCE(session_date, DATE(created_at))) = ?', [$now->month])
            ->count();

        $pendingLeaves = DB::table('teacher_leaves')
            ->where('teacher_id', $teacher->id)->where('status', 'Pending')->count();

        $stats = [
            'students'   => $totalStudents,
            'new'        => $newStudents,
            'classes'    => $classes->count(),
            'online'     => $classes->where('delivery_mode', 'Online')->count(),
            'offline'    => $classes->where('delivery_mode', 'Offline')->count(),
            'sessions'   => $sessionsMonth,
            'leaves'     => $pendingLeaves,
        ];

        // ---------- CLASSES TODAY ----------
        $todayClasses = DB::table('class_schedules as cs')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->where('c.teacher_id', $teacher->id)
            ->where('c.teacher_response', 'Accepted')
            ->whereIn('c.status', ['Open', 'Running'])
            ->where('cs.day', self::DAYS[$now->dayOfWeekIso])
            ->select('cs.id', 'cs.start_time', 'cs.end_time', 'cs.room', 'c.class_name', 'c.delivery_mode',
                DB::raw("(select count(*) from class_enrollments ce where ce.class_id = c.id and ce.status = 'Active') as students"))
            ->orderBy('cs.start_time')->get();

        // Jadwal dianggap selesai jika sudah ada jurnal untuk jadwal & tanggal ini,
        // SIAPA PUN yang mengisi (guru pengganti juga dihitung).
        $filledIds = $todayClasses->isEmpty() ? [] : DB::table('teaching_journals')
            ->whereIn('class_schedule_id', $todayClasses->pluck('id'))
            ->whereRaw('COALESCE(session_date, DATE(created_at)) = ?', [$today])
            ->pluck('class_schedule_id')->map(fn ($id) => (int) $id)->all();

        $highlighted = false;
        $todayClasses = $todayClasses->map(function ($s) use ($now, $today, $filledIds, &$highlighted) {
            $start = Carbon::parse("$today {$s->start_time}");
            $end   = Carbon::parse("$today {$s->end_time}");

            if (in_array((int) $s->id, $filledIds, true)) {
                $s->state = 'completed'; $s->label = 'selesai';
            } elseif ($now->between($start, $end)) {
                $s->state = 'ongoing'; $s->label = 'sedang berlangsung';
            } elseif ($now->lt($start)) {
                $s->state = 'upcoming'; $s->label = $start->locale('id')->diffForHumans();
            } else {
                $s->state = 'missed'; $s->label = 'jurnal belum diisi';
            }

            $s->highlight = false;
            if (!$highlighted && in_array($s->state, ['ongoing', 'upcoming'])) {
                $s->highlight = true; $highlighted = true;
            }
            return $s;
        });

        // ---------- RECENT ACTIVITY ----------
        $activities = collect();

        // Satu baris per SESI (bukan per siswa).
        DB::table('teaching_journals as j')
            ->join('classes as c', 'c.id', '=', 'j.class_id')
            ->where('j.teacher_id', $teacher->id)
            ->orderByDesc('j.created_at')->limit(4)
            ->get([
                'j.class_status', 'c.class_name', 'j.created_at',
                DB::raw("(select count(*) from attendances a where a.teaching_journal_id = j.id and a.status = 'Present') as hadir"),
                DB::raw("(select count(*) from attendances a where a.teaching_journal_id = j.id and a.status <> 'Present') as tidak_hadir"),
            ])
            ->each(function ($r) use ($activities) {
                $conducted = $r->class_status === 'Conducted';
                $activities->push([
                    'time'  => Carbon::parse($r->created_at),
                    'title' => $conducted ? "Sesi {$r->class_name} terlaksana" : "Sesi {$r->class_name} tidak terlaksana ({$r->class_status})",
                    'sub'   => 'Attendance tercatat',
                    'badge' => $conducted ? "{$r->hadir} hadir · {$r->tidak_hadir} tidak hadir" : $r->class_status,
                    'tone'  => !$conducted || $r->tidak_hadir > 0 ? 'warn' : 'ok',
                    'name'  => $r->class_name,
                ]);
            });

        DB::table('progress_reports as pr')
            ->join('students as s', 's.id', '=', 'pr.student_id')
            ->where('pr.teacher_id', $teacher->id)
            ->orderByDesc('pr.created_at')->limit(4)
            ->get(['s.name', 'pr.status', 'pr.report_period', 'pr.created_at'])
            ->each(function ($r) use ($activities) {
                $activities->push([
                    'time'  => Carbon::parse($r->created_at),
                    'title' => "Progress report {$r->name} ({$r->report_period})",
                    'sub'   => $r->status === 'Submitted' ? 'Menunggu review Kurikulum' : 'Disimpan sebagai draft',
                    'badge' => $r->status === 'Submitted' ? 'Diajukan' : 'Draft',
                    'tone'  => 'warn',
                    'name'  => $r->name,
                ]);
            });

        DB::table('teacher_leaves as tl')
            ->join('class_schedules as cs', 'cs.id', '=', 'tl.class_schedule_id')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->where('tl.teacher_id', $teacher->id)
            ->orderByDesc('tl.created_at')->limit(3)
            ->get(['tl.status', 'c.class_name', 'tl.created_at'])
            ->each(function ($r) use ($activities) {
                $map = ['Approved' => ['Disetujui', 'ok'], 'Rejected' => ['Ditolak', 'bad'], 'Pending' => ['Menunggu', 'warn']];
                $activities->push([
                    'time'  => Carbon::parse($r->created_at),
                    'title' => "Pengajuan cuti/ganti kelas {$r->class_name}",
                    'sub'   => 'Dikirim ke Kepala Kurikulum',
                    'badge' => $map[$r->status][0],
                    'tone'  => $map[$r->status][1],
                    'name'  => 'CT',
                ]);
            });

        $activities = $activities->sortByDesc('time')->take(6)->values();

        // ---------- PERLU TINDAKAN (sumber sama dengan lonceng & halaman Notifikasi) ----------
        $pendingActions = collect(TeacherNotificationService::for($teacher)->bell()['rows'])
            ->where('type', '!=', 'info')->values();

        $hour = (int) $now->format('H');
        $greeting = $hour < 11 ? 'Good morning' : ($hour < 15 ? 'Good afternoon' : ($hour < 19 ? 'Good evening' : 'Good night'));

        return view('guru.dashboard', compact('teacher', 'greeting', 'stats', 'classes', 'todayClasses', 'activities', 'pendingActions'));
    }
}