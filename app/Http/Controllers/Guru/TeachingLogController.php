<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
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
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
        5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
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
        ->get(['id', 'class_name', 'program_package_id', 'private_package_id', 'status', 'start_date', 'end_date']);

        $selectedClassIds = $classId ? [(int) $classId] : $myClasses->pluck('id')->all();
        $classMap = $myClasses->keyBy('id');

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
        // Ambil sesi nyata minggu/bulan ini (class_sessions) Regular Scheduled/Completed
        $sessions = ClassSession::with(['class', 'schedule', 'journal'])
            ->whereIn('class_id', $selectedClassIds)
            ->where('kind', 'Regular')
            ->whereIn('status', ['Scheduled', 'Completed'])
            ->whereBetween('session_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('start_time')
            ->get();

        // Cari juga sesi yang aslinya pada tanggal tertentu tapi sudah dipindah keluar dari tanggal tersebut
        $movedOutClassDatePairs = ClassSession::whereIn('class_id', $selectedClassIds)
            ->where('kind', 'Regular')
            ->where('status', '!=', 'Cancelled')
            ->whereNotNull('original_date')
            ->whereColumn('session_date', '!=', 'original_date')
            ->get(['class_id', 'original_date'])
            ->map(fn ($item) => $item->class_id . '_' . Carbon::parse($item->original_date)->toDateString())
            ->flip();

        // Kumpulan class_id dan tanggal yang sudah memiliki sesi nyata pada bulan ini
        $existingClassDates = $sessions->map(fn ($s) => $s->class_id . '_' . $s->session_date->toDateString())->flip();

        // Pola mingguan class_schedules aktif
        $schedules = ClassSchedule::whereIn('class_id', $selectedClassIds)
            ->with(['class'])
            ->get();

        // Ambil semua jurnal dan unlock yang relevan pada rentang tanggal ini
        $journals = TeachingJournal::whereIn('class_id', $selectedClassIds)
            ->whereBetween('session_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $journalsBySession = $journals->whereNotNull('class_session_id')->keyBy('class_session_id');

        $allScheduleIds = $schedules->pluck('id')->merge($sessions->pluck('class_schedule_id'))->filter()->unique()->values()->all();

        $unlocks = ScheduleJournalUnlock::whereIn('class_schedule_id', $allScheduleIds)
            ->whereBetween('session_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('unlocked_until', '>', now())
            ->get();

        // Helper check unlock
        $isSessionUnlocked = function (?int $schedId, string $dateStr) use ($unlocks) {
            if (!$schedId) return false;
            return $unlocks->contains(function ($u) use ($schedId, $dateStr) {
                return (int) $u->class_schedule_id === (int) $schedId && $u->session_date->format('Y-m-d') === $dateStr;
            });
        };

        // Susun daftar sesi pertemuan per tanggal dalam bulan tersebut
        $sessionsList = collect();
        $journalFilledCount = 0;
        $journalUnfilledCount = 0;
        $journalLateCount = 0;

        // 1) Masukkan SEMUA class_sessions nyata pada bulan ini
        foreach ($sessions as $cs) {
            $class = $cs->class ?? $classMap->get($cs->class_id);
            if (!$class) continue;

            $dateStr = $cs->session_date->toDateString();
            $dayName = self::DAYS[$cs->session_date->dayOfWeekIso] ?? $cs->session_date->format('l');

            $startTime = substr($cs->start_time, 0, 5);
            $endTime = substr($cs->end_time, 0, 5);
            $sessionEnd = Carbon::parse($dateStr . ' ' . $endTime);
            $deadline = $sessionEnd->copy()->addHours(24);
            $isPastDeadline = now()->gt($deadline);

            $journal = $journalsBySession->get($cs->id)
                ?? $journals->first(fn ($j) => (int) $j->class_id === (int) $cs->class_id && $j->session_date->format('Y-m-d') === $dateStr);

            $isUnlocked = $isSessionUnlocked($cs->class_schedule_id, $dateStr);

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

            // Status progress report kelas ini
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
                'date'             => $cs->session_date->copy(),
                'day'              => $dayName,
                'class_id'         => $cs->class_id,
                'class_name'       => $class->class_name ?? '-',
                'schedule_id'      => $cs->class_schedule_id,
                'start_time'       => $startTime,
                'end_time'         => $endTime,
                'meeting_number'   => $journal?->meeting_number ?? ($cs->session_number ?? '-'),
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

        // 2) Fallback ke pola class_schedules untuk kelas yang belum punya sesi di tanggal tersebut
        $dayMap = array_flip(self::DAYS);

        foreach ($schedules as $sched) {
            $dayNum = $dayMap[$sched->day] ?? null;
            if (!$dayNum) continue;

            $class = $sched->class ?? $classMap->get($sched->class_id);
            if (!$class) continue;

            $classStartDate = $class->start_date ? Carbon::parse($class->start_date)->startOfDay() : null;
            $classEndDate = $class->end_date ? Carbon::parse($class->end_date)->endOfDay() : null;
            $schedEffFrom = $sched->effective_from ? Carbon::parse($sched->effective_from)->startOfDay() : null;
            $schedEffUntil = $sched->effective_until ? Carbon::parse($sched->effective_until)->endOfDay() : null;

            // Loop tiap hari dalam bulan
            $cur = $startDate->copy();
            while ($cur->lte($endDate)) {
                if ($cur->dayOfWeekIso === $dayNum) {
                    $dateStr = $cur->toDateString();
                    $classDateKey = $sched->class_id . '_' . $dateStr;

                    // Lewati jika sudah ada sesi nyata atau aslinya di tanggal ini tapi dipindah ke luar rentang
                    if ($existingClassDates->has($classDateKey) || $movedOutClassDatePairs->has($classDateKey)) {
                        $cur->addDay();
                        continue;
                    }

                    // Terapkan batasan start_date kelas & effective_from / effective_until jadwal fallback
                    if ($classStartDate && $cur->lt($classStartDate)) {
                        $cur->addDay();
                        continue;
                    }
                    if ($classEndDate && $cur->gt($classEndDate)) {
                        $cur->addDay();
                        continue;
                    }
                    if ($schedEffFrom && $cur->lt($schedEffFrom)) {
                        $cur->addDay();
                        continue;
                    }
                    if ($schedEffUntil && $cur->gt($schedEffUntil)) {
                        $cur->addDay();
                        continue;
                    }

                    $sessionEnd = Carbon::parse($dateStr . ' ' . $sched->end_time);
                    $deadline = $sessionEnd->copy()->addHours(24);
                    $isPastDeadline = now()->gt($deadline);

                    $journal = $journals->first(fn ($j) => (int) $j->class_id === (int) $sched->class_id && $j->session_date->format('Y-m-d') === $dateStr);
                    $isUnlocked = $isSessionUnlocked($sched->id, $dateStr);

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
                        'class_name'       => $class->class_name ?? '-',
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
