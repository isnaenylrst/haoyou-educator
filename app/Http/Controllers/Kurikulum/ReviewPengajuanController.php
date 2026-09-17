<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\ProgressReport;
use App\Models\TeacherLeave;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReviewPengajuanController extends Controller
{
    public function index()
    {
        $today = Carbon::now();

        $todayName = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ][$today->format('l')];

        /*
        |--------------------------------------------------------------------------
        | CARD SESI - JURNAL & ABSENSI HARI INI
        |--------------------------------------------------------------------------
        */

        $todaySchedules = ClassSchedule::with([
                'class.teacher',
                'class.programPackage.program',
                'teachingJournals' => function ($q) use ($today) {
                    $q->whereDate('created_at', $today->toDateString())
                        ->with(['attendances.student', 'material']);
                },
            ])
            ->whereHas('class', fn ($q) => $q->whereIn('status', ['Open', 'Running']))
            ->where('day', $todayName)
            ->orderBy('start_time')
            ->get();

        $totalSessionToday = $todaySchedules->count();
        $filledToday = $todaySchedules->filter(fn ($s) => $s->teachingJournals->isNotEmpty())->count();
        $unfilledToday = $totalSessionToday - $filledToday;

        $teacherNamesToday = $todaySchedules
            ->pluck('class.teacher.name')
            ->filter()
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | REVIEW DOKUMEN PER KATEGORI
        |--------------------------------------------------------------------------
        | Catatan: di database ini, LP & PPT digabung dalam 1 tabel
        | (teacher_materials) - tidak ada kolom LP terpisah. Jadi tabnya
        | digabung jadi "LP & PPT".
        |--------------------------------------------------------------------------
        */

        $materials = TeacherMaterial::with(['teacher', 'material.classroom'])
            ->where('status', 'Pending')
            ->latest()
            ->get();

        $journals = TeachingJournal::with(['teacher', 'class', 'classSchedule'])
            ->where('status', 'Pending')
            ->latest()
            ->get();

        $reports = ProgressReport::with(['student', 'teacher', 'enrollment.class'])
            ->where('status', 'Submitted')
            ->whereNull('reviewed_at')
            ->orderByDesc('uploaded_at')
            ->get();

        $leaves = TeacherLeave::with(['teacher', 'classSchedule.class', 'replacementTeacher'])
            ->where('status', 'Pending')
            ->latest()
            ->get();

        $totalPending = $materials->count() + $journals->count() + $reports->count() + $leaves->count();

        return view('kurikulum.review-pengajuan', compact(
            'todaySchedules',
            'totalSessionToday',
            'filledToday',
            'unfilledToday',
            'teacherNamesToday',
            'materials',
            'journals',
            'reports',
            'leaves',
            'totalPending'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Tandai ACC sesi (dari Card Sesi paling atas)
    |--------------------------------------------------------------------------
    */

    public function ackSession(TeachingJournal $teachingJournal)
    {
        $teachingJournal->update(['status' => 'Reviewed']);

        return back()->with('success', 'Sesi berhasil di-ACC.');
    }

    public function sendReminder(ClassSchedule $classSchedule)
    {
        // TODO: integrasikan ke WhatsApp API kalau sudah tersedia.
        return back()->with('success', 'Reminder berhasil dikirim ke guru (simulasi - WA API belum diintegrasikan).');
    }

    /*
    |--------------------------------------------------------------------------
    | LP & PPT
    |--------------------------------------------------------------------------
    */

    public function reviewMaterial(Request $request, TeacherMaterial $teacherMaterial)
    {
        $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
        ]);

        $teacherMaterial->update([
            'status' => $request->decision,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'LP & PPT berhasil ' . ($request->decision === 'Approved' ? 'di-ACC' : 'dikembalikan ke guru') . '.');
    }

    /*
    |--------------------------------------------------------------------------
    | Jurnal Online
    |--------------------------------------------------------------------------
    */

    public function reviewJournal(Request $request, TeachingJournal $teachingJournal)
    {
        $request->validate([
            'decision' => ['required', Rule::in(['Reviewed', 'Revision'])],
        ]);

        $teachingJournal->update(['status' => $request->decision]);

        return back()->with('success', 'Jurnal berhasil ' . ($request->decision === 'Reviewed' ? 'di-ACC' : 'dikembalikan untuk revisi') . '.');
    }

    /*
    |--------------------------------------------------------------------------
    | Progress Report
    |--------------------------------------------------------------------------
    | Tidak ada status Approved/Rejected di database ini, jadi:
    | - ACC   -> tandai reviewed_at (status tetap Submitted)
    | - Kembalikan -> status balik ke Draft (guru upload ulang)
    |--------------------------------------------------------------------------
    */

    public function reviewReport(Request $request, ProgressReport $progressReport)
    {
        $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
        ]);

        if ($request->decision === 'Approved') {
            $progressReport->update([
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        } else {
            $progressReport->update([
                'status' => 'Draft',
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
        }

        return back()->with('success', 'Progress report berhasil ' . ($request->decision === 'Approved' ? 'di-ACC' : 'dikembalikan ke guru') . '.');
    }

    /*
    |--------------------------------------------------------------------------
    | Cuti / Pengajuan Kelas
    |--------------------------------------------------------------------------
    */

    public function reviewLeave(Request $request, TeacherLeave $teacherLeave)
    {
        $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
        ]);

        $teacherLeave->update([
            'status' => $request->decision,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan berhasil ' . ($request->decision === 'Approved' ? 'disetujui' : 'ditolak') . '.');
    }
}