<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ProgressReport;
use App\Models\ScheduleJournalUnlock;
use App\Models\TeachingJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeachingLogController extends Controller
{
    private const DAYS = [
        'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4,
        'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7,
    ];

    public function index(Request $request)
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403, 'Akun Anda belum terhubung dengan data guru.');

        // Filter periode (default: bulan ini) & kelas
        $month   = $request->get('month', now()->format('Y-m'));
        $classId = $request->get('class_id');

        try {
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endDate   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        } catch (\Throwable $e) {
            $month     = now()->format('Y-m');
            $startDate = now()->startOfMonth();
            $endDate   = now()->endOfMonth();
        }

        // Daftar kelas aktif guru ini
        $myClasses = ClassModel::where(function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id)
              ->where('teacher_response', 'Accepted');
        })
        ->orderBy('class_name')
        ->get(['id', 'class_name', 'program_package_id', 'private_package_id', 'status']);

        $selectedClassIds = $classId ? [$classId] : $myClasses->pluck('id')->all();

        // 1. RINGKASAN PROGRESS REPORT (PER KELAS & PERIODE)
        $enrollmentQuery = DB::table('class_enrollments as ce')
            ->join('classes as c', 'c.id', '=', 'ce.class_id')
            ->whereIn('ce.class_id', $selectedClassIds)
            ->where('ce.status', 'Active');

        $activeEnrollmentIds = (clone $enrollmentQuery)->pluck('ce.id')->all();
        $totalActiveStudents = count($activeEnrollmentIds);

        $reports = ProgressReport::whereIn('enrollment_id', $activeEnrollmentIds)->get();

        $prApproved = $reports->filter(fn ($r) => in_array($r->status, ['Submitted', 'Published']) && $r->reviewed_at !== null)->count();
        $prSubmitted = $reports->filter(fn ($r) => $r->status === 'Submitted' && $r->reviewed_at === null)->count();
        $prDraft = $reports->filter(fn ($r) => $r->status === 'Draft')->count();
        $prRevision = $reports->filter(fn ($r) => $r->review_note !== null && in_array($r->status, ['Draft', 'Revision']))->count();
        $prUnfilled = max(0, $totalActiveStudents - ($prApproved + $prSubmitted + $prDraft));

        // 2. RINGKASAN JURNAL MENGAJAR (PERIODE TERPILIH)
        // Ambil jadwal kelas yang relevan
        $schedules = ClassSchedule::whereIn('class_id', $selectedClassIds)
            ->with(['class'])
            ->get();

        $journals = TeachingJournal::whereIn('class_id', $selectedClassIds)
            ->whereBetween('session_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $unlocks = ScheduleJournalUnlock::whereIn('class_schedule_id', $schedules->pluck('id'))
            ->whereBetween('session_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('unlocked_until', '>', now())
            ->get()
            ->keyBy(fn ($u) => $u->class_schedule_id . '_' . $u->session_date->format('Y-m-d'));

        // Susun daftar sesi pertemuan per tanggal dalam bulan tersebut
        $sessionsList = collect();
        $journalFilledCount = 0;
        $journalUnfilledCount = 0;
        $journalLateCount = 0;

        foreach ($schedules as $sched) {
            $dayNum = self::DAYS[$sched->day] ?? null;
            if (!$dayNum) continue;

            // Loop tiap hari dalam bulan
            $cur = $startDate->copy();
            while ($cur->lte($endDate)) {
                if ($cur->dayOfWeekIso === $dayNum) {
                    $dateStr = $cur->toDateString();
                    $sessionEnd = Carbon::parse($dateStr . ' ' . $sched->end_time);
                    $deadline = $sessionEnd->copy()->addHours(24);
                    $isPastDeadline = now()->gt($deadline);

                    $journal = $journals->first(fn ($j) => $j->class_schedule_id == $sched->id && $j->session_date->format('Y-m-d') === $dateStr);
                    $unlockKey = $sched->id . '_' . $dateStr;
                    $isUnlocked = isset($unlocks[$unlockKey]);

                    // Tentukan status jurnal
                    if ($journal) {
                        $jStatus = 'Terisi';
                        $jBadge = 'bg-success';
                        $journalFilledCount++;
                    } elseif ($isPastDeadline && !$isUnlocked) {
                        $jStatus = 'Terlambat';
                        $jBadge = 'bg-danger';
                        $journalLateCount++;
                    } else {
                        $jStatus = 'Belum Diisi';
                        $jBadge = 'bg-warning text-dark';
                        $journalUnfilledCount++;
                    }

                    // Tentukan status progress report kelas ini
                    $classReports = $reports->where('class_id', $sched->class_id);
                    if ($prApproved > 0 && $prUnfilled === 0) {
                        $prStatus = 'Disetujui';
                        $prBadge = 'bg-success';
                    } elseif ($prSubmitted > 0) {
                        $prStatus = 'Diajukan';
                        $prBadge = 'bg-info text-dark';
                    } elseif ($prRevision > 0) {
                        $prStatus = 'Perlu Revisi';
                        $prBadge = 'bg-danger';
                    } elseif ($prDraft > 0) {
                        $prStatus = 'Draft';
                        $prBadge = 'bg-secondary';
                    } else {
                        $prStatus = 'Belum Diisi';
                        $prBadge = 'bg-light text-dark border';
                    }

                    $sessionsList->push((object) [
                        'date'             => $cur->copy(),
                        'day'              => $sched->day,
                        'class_id'         => $sched->class_id,
                        'class_name'       => $sched->class?->class_name ?? '-',
                        'schedule_id'      => $sched->id,
                        'start_time'       => substr($sched->start_time, 0, 5),
                        'end_time'         => substr($sched->end_time, 0, 5),
                        'meeting_number'   => $journal?->meeting_number ?? '-',
                        'journal'          => $journal,
                        'journal_status'   => $jStatus,
                        'journal_badge'    => $jBadge,
                        'pr_status'        => $prStatus,
                        'pr_badge'         => $prBadge,
                        'is_past_deadline' => $isPastDeadline,
                        'is_unlocked'      => $isUnlocked,
                        'deadline'         => $deadline,
                    ]);
                }
                $cur->addDay();
            }
        }

        // Urutkan pertemuan dari tanggal terbaru
        $sortedSessions = $sessionsList->sortByDesc('date')->values();

        // Pagination manual untuk daftar pertemuan
        $page = (int) $request->get('page', 1);
        $perPage = 15;
        $paginatedSessions = new \Illuminate\Pagination\LengthAwarePaginator(
            $sortedSessions->forPage($page, $perPage),
            $sortedSessions->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $stats = [
            // Progress report summary
            'pr_approved'      => $prApproved,
            'pr_submitted'     => $prSubmitted,
            'pr_draft'         => $prDraft,
            'pr_unfilled'      => $prUnfilled,
            'total_students'   => $totalActiveStudents,

            // Jurnal summary
            'journal_filled'   => $journalFilledCount,
            'journal_unfilled' => $journalUnfilledCount,
            'journal_late'     => $journalLateCount,
            'total_sessions'   => $sessionsList->count(),
        ];

        return view('guru.teaching-log', compact(
            'myClasses', 'classId', 'month', 'stats', 'paginatedSessions'
        ));
    }
}
