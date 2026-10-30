<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ProgressReport;
use App\Services\ProgressReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard Owner: keuangan, siswa & retensi, ketepatan guru, kelas & operasional,
 * marketing (CRM), mutu akademik, dan peringatan otomatis.
 * Semua angka dihitung dari data nyata; satu kueri yang gagal tidak merusak halaman.
 */
class DashboardController extends Controller
{
    /** Jurnal dianggap TEPAT WAKTU jika dibuat paling lambat 1 hari setelah tanggal sesi. */
    private const JOURNAL_GRACE_DAYS = 1;

    public function index()
    {
        $today  = now();
        $months = $this->lastMonths(12);

        $fin = $this->finance($today, $months);
        $stu = $this->students($today, $months);
        $tea = $this->teachers($today, $months);
        $cls = $this->classes($today, $months);
        $mkt = $this->marketing($today, $months);
        $aca = $this->academic($today, $months);

        $alerts      = $this->alerts($today, $fin, $stu, $tea, $cls, $mkt, $aca);
        $monthLabel  = $today->copy()->locale('id')->translatedFormat('F Y');
        $monthLabels = array_column($months, 'label');

        return view('owner.dashboard', compact(
            'monthLabel', 'monthLabels', 'fin', 'stu', 'tea', 'cls', 'mkt', 'aca', 'alerts'
        ));
    }

    // ======================================================================
    // KEUANGAN
    // ======================================================================
    private function finance(Carbon $today, array $months): array
    {
        $keys   = array_column($months, 'key');
        $since  = Carbon::parse($keys[0] . '-01')->toDateString();
        $to     = $today->toDateString();
        $year   = (int) $today->year;

        $sum = fn ($a, $b) => (float) DB::table('payments')
            ->whereNull('deleted_at')
            ->whereBetween('payment_date', [$a, $b])
            ->sum('amount_paid');

        $mFrom = $today->copy()->startOfMonth()->toDateString();
        $pFrom = $today->copy()->startOfMonth()->subMonthNoOverflow()->toDateString();
        $pTo   = $today->copy()->startOfMonth()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $monthNow  = $this->safe(fn () => $sum($mFrom, $to), 0.0);
        $monthPrev = $this->safe(fn () => $sum($pFrom, $pTo), 0.0);

        $ytd     = $this->safe(fn () => $sum($today->copy()->startOfYear()->toDateString(), $to), 0.0);
        $ytdPrev = $this->safe(fn () => $sum(
            $today->copy()->subYear()->startOfYear()->toDateString(),
            $today->copy()->subYear()->toDateString()
        ), 0.0);

        // 12 bulan + pembanding tahun lalu (bulan yang sama)
        $rows = $this->safe(fn () => DB::table('payments')
            ->whereNull('deleted_at')
            ->where('payment_date', '>=', Carbon::parse($keys[0] . '-01')->subYear()->toDateString())
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount_paid) as total")
            ->groupByRaw("DATE_FORMAT(payment_date, '%Y-%m')")
            ->pluck('total', 'ym'), collect());

        $series     = array_map(fn ($k) => (float) ($rows[$k] ?? 0), $keys);
        $prevSeries = array_map(fn ($k) => (float) ($rows[Carbon::parse($k . '-01')->subYear()->format('Y-m')] ?? 0), $keys);

        // 3 tahun
        $yrows = $this->safe(fn () => DB::table('payments')
            ->whereNull('deleted_at')
            ->whereRaw('YEAR(payment_date) >= ?', [$year - 2])
            ->selectRaw('YEAR(payment_date) as yr, SUM(amount_paid) as total')
            ->groupByRaw('YEAR(payment_date)')
            ->pluck('total', 'yr'), collect());

        $years = [];
        foreach ([$year - 2, $year - 1, $year] as $i => $y) {
            $total  = (float) ($yrows[$y] ?? 0);
            $before = $i > 0 ? $years[$i - 1]['total'] : null;

            $years[] = [
                'year'   => $y,
                'total'  => $total,
                'growth' => ($before !== null && $before > 0) ? (int) round((($total - $before) / $before) * 100) : null,
            ];
        }

        // rata-rata 3 bulan penuh terakhir + proyeksi akhir tahun
        $full       = array_slice($series, -4, 3);
        $avg3       = count($full) ? array_sum($full) / count($full) : 0.0;
        $projection = $ytd + $avg3 * (12 - (int) $today->month);

        // per metode & per program (12 bulan)
        $byMethod = $this->safe(fn () => DB::table('payments')
            ->whereNull('deleted_at')
            ->where('payment_date', '>=', $since)
            ->selectRaw("COALESCE(payment_method, 'Lainnya') as label, SUM(amount_paid) as total")
            ->groupByRaw("COALESCE(payment_method, 'Lainnya')")
            ->orderByDesc('total')
            ->get(), collect());

        $byProgram = $this->safe(fn () => DB::table('payments as p')
            ->join('class_enrollments as e', 'e.id', '=', 'p.enrollment_id')
            ->leftJoin('program_packages as pp', 'pp.id', '=', 'e.program_package_id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'pp.program_id')
            ->whereNull('p.deleted_at')
            ->whereNull('e.deleted_at')
            ->where('p.payment_date', '>=', $since)
            ->selectRaw("COALESCE(pr.program_name, 'Private') as label, SUM(p.amount_paid) as total")
            ->groupByRaw("COALESCE(pr.program_name, 'Private')")
            ->orderByDesc('total')
            ->get(), collect());

        // piutang (pembayaran terakhir tiap pendaftaran)
        $latest = fn () => DB::table('payments')
            ->whereNull('deleted_at')
            ->selectRaw('MAX(id) as id')
            ->groupBy('enrollment_id');

        $unpaid = $this->safe(fn () => DB::table('payments as p')
            ->joinSub($latest(), 'l', 'l.id', '=', 'p.id')
            ->join('class_enrollments as e', 'e.id', '=', 'p.enrollment_id')
            ->join('students as s', 's.id', '=', 'e.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 'e.class_id')
            ->whereNull('e.deleted_at')
            ->where('p.remaining_bill', '>', 0)
            ->orderByDesc('p.remaining_bill')
            ->get([
                's.name as student', 'c.class_name', 'p.payment_stage',
                'p.total_bill', 'p.amount_paid', 'p.remaining_bill', 'p.payment_date',
            ]), collect());

        $aging = [
            '0-30 hari'  => ['count' => 0, 'total' => 0.0],
            '31-60 hari' => ['count' => 0, 'total' => 0.0],
            '61-90 hari' => ['count' => 0, 'total' => 0.0],
            '> 90 hari'  => ['count' => 0, 'total' => 0.0],
        ];

        foreach ($unpaid as $u) {
            $days = (int) abs(Carbon::parse($u->payment_date)->startOfDay()->diffInDays($today->copy()->startOfDay()));
            $b    = $days <= 30 ? '0-30 hari' : ($days <= 60 ? '31-60 hari' : ($days <= 90 ? '61-90 hari' : '> 90 hari'));

            $aging[$b]['count']++;
            $aging[$b]['total'] += (float) $u->remaining_bill;
        }

        $coll = $this->safe(fn () => DB::table('payments as p')
            ->joinSub($latest(), 'l', 'l.id', '=', 'p.id')
            ->selectRaw('SUM(p.total_bill) as billed, SUM(p.total_bill - p.remaining_bill) as paid')
            ->first(), null);

        $collectionRate = ($coll && (float) $coll->billed > 0)
            ? round(((float) $coll->paid / (float) $coll->billed) * 100, 1)
            : null;

        return [
            'monthNow'       => $monthNow,
            'monthPrev'      => $monthPrev,
            'ytd'            => $ytd,
            'ytdPrev'        => $ytdPrev,
            'series'         => $series,
            'prevSeries'     => $prevSeries,
            'years'          => $years,
            'avg3'           => $avg3,
            'projection'     => $projection,
            'byMethod'       => $byMethod,
            'byProgram'      => $byProgram,
            'unpaidCount'    => $unpaid->count(),
            'unpaidList'     => $unpaid->take(8),
            'outstanding'    => (float) $unpaid->sum('remaining_bill'),
            'aging'          => $aging,
            'collectionRate' => $collectionRate,
        ];
    }

    // ======================================================================
    // SISWA & RETENSI
    // ======================================================================
    private function students(Carbon $today, array $months): array
    {
        $keys  = array_column($months, 'key');
        $since = Carbon::parse($keys[0] . '-01')->toDateString();
        $to    = $today->toDateString();

        $status = $this->safe(fn () => DB::table('students')
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status'), collect());

        $newNow = $this->safe(fn () => (int) DB::table('students')
            ->whereBetween('join_date', [$today->copy()->startOfMonth()->toDateString(), $to])->count(), 0);

        $newPrev = $this->safe(fn () => (int) DB::table('students')
            ->whereBetween('join_date', [
                $today->copy()->startOfMonth()->subMonthNoOverflow()->toDateString(),
                $today->copy()->startOfMonth()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ])->count(), 0);

        $newRows = $this->safe(fn () => DB::table('students')
            ->where('join_date', '>=', $since)
            ->selectRaw("DATE_FORMAT(join_date, '%Y-%m') as ym, COUNT(*) as total")
            ->groupByRaw("DATE_FORMAT(join_date, '%Y-%m')")
            ->pluck('total', 'ym'), collect());

        // semua pendaftaran (tanpa yang dibatalkan), urut per siswa
        $enr = $this->safe(fn () => DB::table('class_enrollments')
            ->whereNull('deleted_at')
            ->where('status', '!=', 'Cancelled')
            ->orderBy('student_id')->orderBy('enrollment_date')->orderBy('id')
            ->get(['id', 'student_id', 'class_id', 'enrollment_date', 'status']), collect());

        $byStudent = $enr->groupBy('student_id');

        $renewRows         = [];
        $completedStudents = 0;
        $renewedStudents   = 0;

        foreach ($byStudent as $list) {
            $list = $list->values();

            // pendaftaran ke-2 dan seterusnya = siswa LANJUT
            foreach ($list as $i => $e) {
                if ($i >= 1 && $e->enrollment_date) {
                    $ym = Carbon::parse($e->enrollment_date)->format('Y-m');
                    $renewRows[$ym] = ($renewRows[$ym] ?? 0) + 1;
                }
            }

            $done = $list->search(fn ($e) => $e->status === 'Completed');
            if ($done !== false) {
                $completedStudents++;
                if ($list->count() > $done + 1) {
                    $renewedStudents++;
                }
            }
        }

        $renewalRate = $completedStudents > 0
            ? round(($renewedStudents / $completedStudents) * 100, 1)
            : null;

        // lama belajar siswa aktif
        $tenure = $this->safe(fn () => DB::table('students')
            ->where('status', 'Active')
            ->get(['id', 'name', 'join_date'])
            ->map(fn ($s) => (object) [
                'name'      => $s->name,
                'join_date' => $s->join_date,
                'months'    => $s->join_date ? (int) abs(Carbon::parse($s->join_date)->diffInMonths($today)) : 0,
                'packages'  => $byStudent->get($s->id, collect())->count(),
            ])
            ->sortByDesc('months')->values(), collect());

        $buckets = ['< 3 bulan' => 0, '3-6 bulan' => 0, '6-12 bulan' => 0, '> 12 bulan' => 0];
        foreach ($tenure as $t) {
            $k = $t->months < 3 ? '< 3 bulan' : ($t->months < 6 ? '3-6 bulan' : ($t->months < 12 ? '6-12 bulan' : '> 12 bulan'));
            $buckets[$k]++;
        }

        // siswa yang paketnya selesai tetapi belum lanjut (perlu follow-up)
        $classEnd = $this->safe(fn () => DB::table('classes')->pluck('end_date', 'id'), collect());
        $names    = $this->safe(fn () => DB::table('students')->get(['id', 'name', 'status'])->keyBy('id'), collect());

        $notContinued = [];
        foreach ($byStudent as $sid => $list) {
            if ($list->contains(fn ($e) => in_array($e->status, ['Active', 'Waiting Class'], true))) {
                continue; // masih punya paket berjalan
            }

            $stu = $names->get($sid);
            if (!$stu || $stu->status === 'Graduated') {
                continue;
            }

            $last = $list->last();
            $end  = $classEnd->get($last->class_id) ?: $last->enrollment_date;
            if (!$end) {
                continue;
            }

            $days = (int) max(0, Carbon::parse($end)->startOfDay()->diffInDays($today->copy()->startOfDay(), false));

            $notContinued[] = [
                'name'     => $stu->name,
                'status'   => $stu->status,
                'packages' => $list->count(),
                'ended'    => $end,
                'days'     => $days,
            ];
        }
        usort($notContinued, fn ($a, $b) => $a['days'] <=> $b['days']);

        return [
            'active'            => (int) ($status['Active'] ?? 0),
            'inactive'          => (int) ($status['Inactive'] ?? 0),
            'graduated'         => (int) ($status['Graduated'] ?? 0),
            'newNow'            => $newNow,
            'newPrev'           => $newPrev,
            'newSeries'         => array_map(fn ($k) => (int) ($newRows[$k] ?? 0), $keys),
            'renewSeries'       => array_map(fn ($k) => (int) ($renewRows[$k] ?? 0), $keys),
            'renewalRate'       => $renewalRate,
            'completedStudents' => $completedStudents,
            'renewedStudents'   => $renewedStudents,
            'avgTenure'         => $tenure->count() ? round($tenure->avg('months'), 1) : null,
            'avgPackages'       => $byStudent->count() ? round($enr->count() / $byStudent->count(), 1) : null,
            'longest'           => $tenure->take(10),
            'tenureBuckets'     => $buckets,
            'notContinued'      => array_slice($notContinued, 0, 10),
            'notContinuedCount' => count($notContinued),
        ];
    }

    // ======================================================================
    // GURU: KETEPATAN & BEBAN
    // ======================================================================
    private function teachers(Carbon $today, array $months): array
    {
        $keys    = array_column($months, 'key');
        $since90 = $today->copy()->subDays(90)->toDateString();
        $grace   = self::JOURNAL_GRACE_DAYS;

        $teachers = $this->safe(fn () => DB::table('teachers')
            ->where('status', 'Active')->orderBy('name')->get(['id', 'name']), collect());

        $journals = $this->safe(fn () => DB::table('teaching_journals')
            ->where('session_date', '>=', $since90)
            ->selectRaw("teacher_id, COUNT(*) as total,
                SUM(CASE WHEN DATEDIFF(DATE(created_at), session_date) <= {$grace} THEN 1 ELSE 0 END) as ontime,
                AVG(GREATEST(DATEDIFF(DATE(created_at), session_date), 0)) as avg_delay")
            ->groupBy('teacher_id')
            ->get()->keyBy('teacher_id'), collect());

        $load = $this->safe(fn () => DB::table('classes as c')
            ->join('class_schedules as cs', 'cs.class_id', '=', 'c.id')
            ->whereIn('c.status', ['Open', 'Running'])
            ->where('c.teacher_response', 'Accepted')
            ->whereNotNull('c.teacher_id')
            ->groupBy('c.teacher_id')
            ->selectRaw('c.teacher_id, COUNT(DISTINCT c.id) as classes, SUM(TIME_TO_SEC(TIMEDIFF(cs.end_time, cs.start_time))) / 3600 as hours')
            ->get()->keyBy('teacher_id'), collect());

        $leaves = $this->safe(fn () => DB::table('teacher_leaves')
            ->where('status', 'Approved')
            ->where('leave_date', '>=', $since90)
            ->selectRaw('teacher_id, COUNT(*) as c')->groupBy('teacher_id')->pluck('c', 'teacher_id'), collect());

        // progress report: terlambat dibanding batas H+3
        $reports = $this->safe(fn () => ProgressReport::whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $today->copy()->subDays(180))
            ->orderByDesc('submitted_at')->limit(200)->get(), collect());

        $classOf = $this->safe(fn () => DB::table('class_enrollments')
            ->whereIn('id', $reports->pluck('enrollment_id')->filter()->all())
            ->pluck('class_id', 'id'), collect());

        $repTotal = [];
        $repLate  = [];

        foreach ($reports as $r) {
            $tid = $r->teacher_id;
            $repTotal[$tid] = ($repTotal[$tid] ?? 0) + 1;

            $cid = $classOf->get($r->enrollment_id);
            if ($cid) {
                $deadline = $this->safe(fn () => ProgressReportService::deadline((int) $cid, $r), null);

                if ($deadline && Carbon::parse($r->submitted_at)->gt($deadline)) {
                    $repLate[$tid] = ($repLate[$tid] ?? 0) + 1;
                }
            }
        }

        $rows       = [];
        $sumTotal   = 0;
        $sumOnTime  = 0;
        $good       = 0;
        $bad        = 0;

        foreach ($teachers as $t) {
            $j      = $journals->get($t->id);
            $l      = $load->get($t->id);
            $total  = (int) ($j->total ?? 0);
            $ontime = (int) ($j->ontime ?? 0);
            $late   = $repLate[$t->id] ?? 0;
            $pct    = $total > 0 ? (int) round(($ontime / $total) * 100) : null;

            if ($pct === null) {
                $label = 'Belum ada data'; $badge = 'secondary';
            } elseif ($pct >= 90 && $late === 0) {
                $label = 'Tepat Waktu'; $badge = 'success'; $good++;
            } elseif ($pct >= 70) {
                $label = 'Perlu Perhatian'; $badge = 'warning';
            } else {
                $label = 'Sering Terlambat'; $badge = 'danger'; $bad++;
            }

            $sumTotal  += $total;
            $sumOnTime += $ontime;

            $rows[] = [
                'name'      => $t->name,
                'classes'   => (int) ($l->classes ?? 0),
                'hours'     => round((float) ($l->hours ?? 0), 1),
                'journals'  => $total,
                'ontime'    => $ontime,
                'pct'       => $pct,
                'avgDelay'  => $j ? round((float) $j->avg_delay, 1) : null,
                'reports'   => (int) ($repTotal[$t->id] ?? 0),
                'reportLate'=> (int) $late,
                'leaves'    => (int) ($leaves[$t->id] ?? 0),
                'label'     => $label,
                'badge'     => $badge,
            ];
        }

        usort($rows, fn ($a, $b) => ($b['pct'] ?? -1) <=> ($a['pct'] ?? -1));

        // tren ketepatan per bulan
        $trendRows = $this->safe(fn () => DB::table('teaching_journals')
            ->where('session_date', '>=', Carbon::parse($keys[0] . '-01')->toDateString())
            ->selectRaw("DATE_FORMAT(session_date, '%Y-%m') as ym, COUNT(*) as total,
                SUM(CASE WHEN DATEDIFF(DATE(created_at), session_date) <= {$grace} THEN 1 ELSE 0 END) as ontime")
            ->groupByRaw("DATE_FORMAT(session_date, '%Y-%m')")
            ->get()->keyBy('ym'), collect());

        $trend = array_map(function ($k) use ($trendRows) {
            $r = $trendRows->get($k);

            return ($r && (int) $r->total > 0) ? round(((int) $r->ontime / (int) $r->total) * 100, 1) : null;
        }, $keys);

        return [
            'rows'       => $rows,
            'overallPct' => $sumTotal > 0 ? round(($sumOnTime / $sumTotal) * 100, 1) : null,
            'journals90' => $sumTotal,
            'good'       => $good,
            'bad'        => $bad,
            'trend'      => $trend,
            'grace'      => $grace,
        ];
    }

    // ======================================================================
    // KELAS & OPERASIONAL
    // ======================================================================
    private function classes(Carbon $today, array $months): array
    {
        $keys    = array_column($months, 'key');
        $since   = Carbon::parse($keys[0] . '-01')->toDateString();
        $since90 = $today->copy()->subDays(90)->toDateString();

        $status = $this->safe(fn () => DB::table('classes')
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status'), collect());

        $rows = $this->safe(fn () => DB::table('classes as c')
            ->leftJoin('teachers as t', 't.id', '=', 'c.teacher_id')
            ->leftJoin('class_enrollments as e', function ($j) {
                $j->on('e.class_id', '=', 'c.id')
                    ->where('e.status', 'Active')
                    ->whereNull('e.deleted_at');
            })
            ->whereIn('c.status', ['Open', 'Running'])
            ->groupBy('c.id', 'c.class_name', 'c.capacity', 'c.status', 'c.teacher_response', 't.name')
            ->selectRaw('c.id, c.class_name, c.capacity, c.status, c.teacher_response, t.name as teacher, COUNT(e.id) as filled')
            ->get(), collect());

        $families = [];
        $capSum   = 0;
        $fillSum  = 0;
        $low      = [];
        $noTeacher = 0;

        foreach ($rows as $r) {
            $f = $this->family($r->class_name);

            $families[$f] ??= ['name' => $f, 'classes' => 0, 'students' => 0, 'capacity' => 0];
            $families[$f]['classes']++;
            $families[$f]['students'] += (int) $r->filled;
            $families[$f]['capacity'] += (int) $r->capacity;

            $capSum  += (int) $r->capacity;
            $fillSum += (int) $r->filled;

            $fill = (int) $r->capacity > 0 ? ((int) $r->filled / (int) $r->capacity) * 100 : null;
            if ($fill !== null && $fill < 50) {
                $low[] = [
                    'name' => $r->class_name, 'teacher' => $r->teacher, 'filled' => (int) $r->filled,
                    'capacity' => (int) $r->capacity, 'fill' => (int) round($fill), 'status' => $r->status,
                ];
            }

            if ($r->teacher === null || $r->teacher_response === 'Rejected') {
                $noTeacher++;
            }
        }

        foreach ($families as &$fam) {
            $fam['fill'] = $fam['capacity'] > 0 ? min(100, (int) round(($fam['students'] / $fam['capacity']) * 100)) : 0;
        }
        unset($fam);
        uasort($families, fn ($a, $b) => $b['students'] <=> $a['students']);

        usort($low, fn ($a, $b) => $a['fill'] <=> $b['fill']);

        $completed = (int) ($status['Completed'] ?? 0);
        $closed    = (int) ($status['Closed'] ?? 0);

        // kehadiran siswa per bulan
        $attRows = $this->safe(fn () => DB::table('attendances')
            ->where('session_date', '>=', $since)
            ->selectRaw("DATE_FORMAT(session_date, '%Y-%m') as ym, COUNT(*) as total,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present")
            ->groupByRaw("DATE_FORMAT(session_date, '%Y-%m')")
            ->get()->keyBy('ym'), collect());

        $attSeries = array_map(function ($k) use ($attRows) {
            $r = $attRows->get($k);

            return ($r && (int) $r->total > 0) ? round(((int) $r->present / (int) $r->total) * 100, 1) : null;
        }, $keys);

        $attNow = $attRows->get($today->format('Y-m'));
        $attRate = ($attNow && (int) $attNow->total > 0)
            ? round(((int) $attNow->present / (int) $attNow->total) * 100, 1)
            : null;

        // siswa berisiko: kehadiran < 75% (minimal 3 sesi, 90 hari terakhir)
        $atRisk = $this->safe(fn () => DB::table('attendances as a')
            ->join('students as s', 's.id', '=', 'a.student_id')
            ->where('a.session_date', '>=', $since90)
            ->where('s.status', 'Active')
            ->groupBy('a.student_id', 's.name')
            ->havingRaw('COUNT(*) >= 3')
            ->havingRaw("SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / COUNT(*) < 0.75")
            ->selectRaw("s.name, COUNT(*) as total, SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present")
            ->orderByRaw("SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / COUNT(*) asc")
            ->limit(10)->get(), collect());

        return [
            'running'     => (int) ($status['Running'] ?? 0),
            'open'        => (int) ($status['Open'] ?? 0),
            'completed'   => $completed,
            'closed'      => $closed,
            'completion'  => ($completed + $closed) > 0 ? round(($completed / ($completed + $closed)) * 100, 1) : null,
            'families'    => $families,
            'utilization' => $capSum > 0 ? round(($fillSum / $capSum) * 100, 1) : null,
            'students'    => $fillSum,
            'capacity'    => $capSum,
            'lowFill'     => array_slice($low, 0, 8),
            'lowFillCount'=> count($low),
            'noTeacher'   => $noTeacher,
            'attSeries'   => $attSeries,
            'attRate'     => $attRate,
            'atRisk'      => $atRisk,
        ];
    }

    // ======================================================================
    // MARKETING (CRM)
    // ======================================================================
    private function marketing(Carbon $today, array $months): array
    {
        $keys  = array_column($months, 'key');
        $since = Carbon::parse($keys[0] . '-01')->toDateString();

        $total = $this->safe(fn () => (int) DB::table('candidate_students')->count(), 0);

        $converted = $this->safe(fn () => (int) DB::table('students')
            ->whereNotNull('candidate_student_id')->count(), 0);

        $trialPlanned = $this->safe(fn () => (int) DB::table('candidate_students')
            ->whereNotNull('trial_date')->count(), 0);

        $trialDone = $this->safe(fn () => (int) DB::table('candidate_students')
            ->where('trial_status', 'Completed')->count(), 0);

        $leadStatus = $this->safe(fn () => DB::table('candidate_students')
            ->selectRaw('lead_status, COUNT(*) as c')->groupBy('lead_status')->pluck('c', 'lead_status'), collect());

        $leadRows = $this->safe(fn () => DB::table('candidate_students')
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->pluck('total', 'ym'), collect());

        $convRows = $this->safe(fn () => DB::table('students')
            ->whereNotNull('candidate_student_id')
            ->where('join_date', '>=', $since)
            ->selectRaw("DATE_FORMAT(join_date, '%Y-%m') as ym, COUNT(*) as total")
            ->groupByRaw("DATE_FORMAT(join_date, '%Y-%m')")
            ->pluck('total', 'ym'), collect());

        $bySource = $this->safe(fn () => DB::table('candidate_students as cs')
            ->leftJoin('students as s', 's.candidate_student_id', '=', 'cs.id')
            ->selectRaw("COALESCE(cs.source, 'Tidak diketahui') as source, COUNT(DISTINCT cs.id) as leads, COUNT(DISTINCT s.id) as converted")
            ->groupByRaw("COALESCE(cs.source, 'Tidak diketahui')")
            ->orderByDesc('leads')
            ->get(), collect());

        $avgDays = $this->safe(fn () => DB::table('students as s')
            ->join('candidate_students as cs', 'cs.id', '=', 's.candidate_student_id')
            ->whereNotNull('s.join_date')
            ->selectRaw('AVG(DATEDIFF(s.join_date, DATE(cs.created_at))) as d')
            ->value('d'), null);

        $followOverdue = $this->safe(fn () => (int) DB::table('follow_ups')
            ->where('status', 'Pending')
            ->whereDate('followup_date', '<', $today->toDateString())
            ->count(), 0);

        $feePending = $this->safe(fn () => (int) DB::table('candidate_students')
            ->where('registration_fee_status', 'Pending')->count(), 0);

        return [
            'total'          => $total,
            'converted'      => $converted,
            'conversionRate' => $total > 0 ? round(($converted / $total) * 100, 1) : null,
            'trialPlanned'   => $trialPlanned,
            'trialDone'      => $trialDone,
            'leadStatus'     => $leadStatus,
            'leadSeries'     => array_map(fn ($k) => (int) ($leadRows[$k] ?? 0), $keys),
            'convSeries'     => array_map(fn ($k) => (int) ($convRows[$k] ?? 0), $keys),
            'bySource'       => $bySource,
            'avgDays'        => $avgDays !== null ? round((float) $avgDays, 1) : null,
            'followOverdue'  => $followOverdue,
            'feePending'     => $feePending,
        ];
    }

    // ======================================================================
    // MUTU AKADEMIK & ANTRIAN REVIEW
    // ======================================================================
    private function academic(Carbon $today, array $months): array
    {
        $keys  = array_column($months, 'key');
        $since = Carbon::parse($keys[0] . '-01')->toDateString();

        $reportRows = $this->safe(fn () => ProgressReport::approved()
            ->where('reviewed_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(reviewed_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupByRaw("DATE_FORMAT(reviewed_at, '%Y-%m')")
            ->pluck('total', 'ym'), collect());

        $certRows = $this->safe(fn () => DB::table('certificates')
            ->where('status', 'Approved')
            ->where('reviewed_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(reviewed_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupByRaw("DATE_FORMAT(reviewed_at, '%Y-%m')")
            ->pluck('total', 'ym'), collect());

        $reportsWaiting = $this->safe(fn () => (int) ProgressReport::where('status', 'Submitted')
            ->whereNull('reviewed_at')->count(), 0);

        $oldestReport = $this->safe(fn () => ProgressReport::where('status', 'Submitted')
            ->whereNull('reviewed_at')->min('submitted_at'), null);

        $certStatus = $this->safe(fn () => DB::table('certificates')
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status'), collect());

        $certScore = $this->safe(fn () => DB::table('certificates')
            ->where('status', 'Approved')->whereNotNull('score')->avg('score'), null);

        $journalsPending = $this->safe(fn () => (int) DB::table('teaching_journals')
            ->where('status', 'Pending')->count(), 0);

        $oldestJournal = $this->safe(fn () => DB::table('teaching_journals')
            ->where('status', 'Pending')->min('session_date'), null);

        $materialsPending = $this->safe(fn () => (int) DB::table('teacher_materials')
            ->where('status', 'Pending')->count(), 0);

        $leavesPending = $this->safe(fn () => (int) DB::table('teacher_leaves')
            ->where('status', 'Pending')->count(), 0);

        $age = fn ($d) => $d ? (int) abs(Carbon::parse($d)->startOfDay()->diffInDays($today->copy()->startOfDay())) : null;

        return [
            'reportSeries'     => array_map(fn ($k) => (int) ($reportRows[$k] ?? 0), $keys),
            'certSeries'       => array_map(fn ($k) => (int) ($certRows[$k] ?? 0), $keys),
            'reportsWaiting'   => $reportsWaiting,
            'reportsOldestAge' => $age($oldestReport),
            'certApproved'     => (int) ($certStatus['Approved'] ?? 0),
            'certPending'      => (int) ($certStatus['Pending'] ?? 0),
            'certScore'        => $certScore !== null ? round((float) $certScore, 1) : null,
            'journalsPending'  => $journalsPending,
            'journalsOldestAge'=> $age($oldestJournal),
            'materialsPending' => $materialsPending,
            'leavesPending'    => $leavesPending,
        ];
    }

    // ======================================================================
    // PERINGATAN OTOMATIS UNTUK OWNER
    // ======================================================================
    private function alerts(Carbon $today, array $fin, array $stu, array $tea, array $cls, array $mkt, array $aca): array
    {
        $a   = [];
        $rp  = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
        $add = function (string $level, string $icon, string $title, string $text, string $url) use (&$a) {
            $a[] = compact('level', 'icon', 'title', 'text', 'url');
        };

        // pendapatan turun (dinilai setelah tanggal 20 agar adil)
        if ($fin['monthPrev'] > 0 && $today->day >= 20) {
            $d = (($fin['monthNow'] - $fin['monthPrev']) / $fin['monthPrev']) * 100;
            if ($d <= -10) {
                $add('danger', 'fa-arrow-trend-down', 'Pendapatan bulan ini turun ' . abs(round($d)) . '%',
                    'Dibanding bulan lalu (' . $rp($fin['monthPrev']) . ').', '#keuangan');
            }
        }

        $old = ($fin['aging']['61-90 hari']['count'] ?? 0) + ($fin['aging']['> 90 hari']['count'] ?? 0);
        if ($old > 0) {
            $amt = ($fin['aging']['61-90 hari']['total'] ?? 0) + ($fin['aging']['> 90 hari']['total'] ?? 0);
            $add('danger', 'fa-file-invoice-dollar', "{$old} tagihan menunggak lebih dari 60 hari",
                'Total sisa tagihan ' . $rp($amt) . '.', '#keuangan');
        }

        if ($stu['notContinuedCount'] > 0) {
            $add('warning', 'fa-user-clock', $stu['notContinuedCount'] . ' siswa selesai paket tetapi belum lanjut',
                'Segera follow-up sebelum mereka berhenti.', '#siswa');
        }

        if ($tea['bad'] > 0) {
            $add('danger', 'fa-clock', $tea['bad'] . ' guru sering terlambat mengisi jurnal',
                'Ketepatan di bawah 70% dalam 90 hari terakhir.', '#guru');
        }

        if ($cls['lowFillCount'] > 0) {
            $add('warning', 'fa-users-slash', $cls['lowFillCount'] . ' kelas keterisian di bawah 50%',
                'Pertimbangkan promosi atau penggabungan kelas.', '#kelas');
        }

        if ($cls['noTeacher'] > 0) {
            $add('danger', 'fa-user-xmark', $cls['noTeacher'] . ' kelas belum punya guru yang menerima',
                'Kelas belum ditugaskan atau ditolak guru.', url('/kurikulum/ketersediaan-guru'));
        }

        if ($mkt['followOverdue'] > 0) {
            $add('warning', 'fa-phone-slash', $mkt['followOverdue'] . ' follow-up calon siswa terlambat',
                'Lead bisa dingin jika tidak dihubungi.', '#marketing');
        }

        if (($aca['reportsOldestAge'] ?? 0) > 3 && $aca['reportsWaiting'] > 0) {
            $add('warning', 'fa-hourglass-half', $aca['reportsWaiting'] . ' progress report menunggu ACC',
                'Yang terlama sudah ' . $aca['reportsOldestAge'] . ' hari.', url('/kurikulum/review-pengajuan'));
        }

        if (empty($a)) {
            $add('success', 'fa-circle-check', 'Tidak ada peringatan',
                'Semua indikator utama dalam kondisi baik.', '#ringkasan');
        }

        return $a;
    }

    // ======================================================================
    // UTIL
    // ======================================================================

    /** N bulan terakhir (termasuk bulan ini), urut dari yang paling lama. */
    private function lastMonths(int $n): array
    {
        $out = [];
        $m   = now()->startOfMonth()->copy()->subMonthsNoOverflow($n - 1);

        for ($i = 0; $i < $n; $i++) {
            $out[] = [
                'key'   => $m->format('Y-m'),
                'label' => $m->copy()->locale('id')->translatedFormat('M y'),
            ];
            $m = $m->copy()->addMonthNoOverflow();
        }

        return $out;
    }

    /** Kelompok jenis kelas dari nama kelas. */
    private function family(string $name): string
    {
        $n = strtolower($name);

        if (str_contains($n, 'maochong')) return 'Maochong';
        if (str_contains($n, 'jianer') || str_contains($n, 'jian er')) return 'Jianer';
        if (str_contains($n, 'hudie')) return 'Hudie';
        if (str_contains($n, 'feixiang')) return 'Feixiang';
        if (str_contains($n, 'hsk')) return 'HSK';
        if (str_contains($n, 'private')) return 'Private';

        return 'Lainnya';
    }

    /** Satu kueri yang gagal tidak boleh membuat seluruh dashboard error. */
    private function safe(callable $fn, $default)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Log::warning('Owner dashboard: ' . $e->getMessage());

            return $default;
        }
    }
}