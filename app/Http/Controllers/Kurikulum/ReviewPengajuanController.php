<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ClassEnrollment;
use App\Models\ClassSchedule;
use App\Models\ProgressReport;
use App\Models\TeacherLeave;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
use Illuminate\Http\Request;
use App\Services\ProgressReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $materials = TeacherMaterial::with(['teacher', 'material.level'])
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
            ->latest()
            ->get();

        $leaves = TeacherLeave::with(['teacher', 'classSchedule.class', 'replacementTeacher'])
            ->where('status', 'Pending')
            ->latest()
            ->get();

        // Report yang melewati batas H+3 dan belum pernah dikirim guru
        $lateEnrollments = $this->lateEnrollments();

        // Sertifikat yang dibuat guru dan menunggu ACC
        $certificates = Certificate::with(['student', 'teacher', 'enrollment.class'])
            ->pendingReview()
            ->latest()
            ->get();

        $totalPending = $materials->count() + $journals->count() + $reports->count() + $leaves->count() + $certificates->count();

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
            'certificates',
            'lateEnrollments',
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
            'note'     => ['nullable', 'string', 'max:1000'],
        ]);

        $teacherMaterial->update([
            'status' => $request->decision,
            'review_note' => $request->decision === 'Rejected' ? $request->note : null,
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
            'note'     => ['nullable', 'required_if:decision,Rejected', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Isi alasan saat mengembalikan progress report ke guru.',
        ]);

        if ($request->decision === 'Approved') {
            // ACC: status tetap Submitted, reviewed_at terisi -> baru tampil di siswa
            $progressReport->update([
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_note' => null,
                'returned_at' => null,
            ]);
        } else {
            // Kembalikan ke guru: guru diberi waktu 3 hari untuk memperbaiki
            $progressReport->update([
                'status'         => 'Draft',
                'reviewed_by'    => null,
                'reviewed_at'    => null,
                'returned_at'    => now(),
                'review_note'    => $request->note,
                'extended_until' => now()->addDays(ProgressReportService::GRACE_DAYS)->toDateString(),
            ]);
        }

        return back()->with('success', 'Progress report berhasil ' . ($request->decision === 'Approved' ? 'di-ACC dan sekarang tampil di siswa' : 'dikembalikan ke guru (batas perbaikan 3 hari)') . '.');
    }

    /*
    |--------------------------------------------------------------------------
    | Buka kembali progress report yang lewat batas H+3
    |--------------------------------------------------------------------------
    | Guru terkunci setelah H+3. Kurikulum bisa memberi waktu tambahan 3 hari.
    |--------------------------------------------------------------------------
    */

    public function reopenReport(ClassEnrollment $enrollment)
    {
        $class = $enrollment->class;

        if (!$class || !$class->teacher_id) {
            return back()->with('error', 'Kelas ini belum punya guru pengampu.');
        }

        $report = ProgressReport::where('enrollment_id', $enrollment->id)
            ->where('status', 'Draft')
            ->latest('id')
            ->first();

        if (!$report) {
            $report = new ProgressReport([
                'student_id'    => $enrollment->student_id,
                'teacher_id'    => $class->teacher_id,
                'enrollment_id' => $enrollment->id,
                'report_type'   => 'Trimester Academic Progress Report',
                'report_period' => '',
                'status'        => 'Draft',
                'uploaded_by'   => Auth::id(),
            ]);
        }

        $report->extended_until = now()->addDays(ProgressReportService::GRACE_DAYS)->toDateString();
        $report->save();

        return back()->with('success', 'Progress report dibuka kembali. Guru punya waktu 3 hari untuk mengisi.');
    }

    /** Siswa yang kelasnya selesai > H+3 tapi belum ada report terkirim. */
    private function lateEnrollments()
    {
        $late = collect();

        $lastMeetings = DB::table('teaching_journals')
            ->where('class_status', 'Conducted')
            ->where('is_last_meeting', 1)
            ->select('class_id', DB::raw('max(session_date) as last_date'))
            ->groupBy('class_id')
            ->get();

        foreach ($lastMeetings as $lm) {
            $deadline = Carbon::parse($lm->last_date)
                ->addDays(ProgressReportService::GRACE_DAYS)
                ->endOfDay();

            // hanya yang baru lewat (60 hari terakhir) supaya data lama tidak membanjiri daftar
            if (!$deadline->lt(now()) || $deadline->lt(now()->subDays(60))) {
                continue;
            }

            $enrollments = ClassEnrollment::with(['student', 'class.teacher'])
                ->where('class_id', $lm->class_id)
                ->whereIn('status', ['Active', 'Completed'])
                ->get();

            foreach ($enrollments as $enrollment) {
                $alreadySent = ProgressReport::where('enrollment_id', $enrollment->id)
                    ->where('status', 'Submitted')
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $draft = ProgressReport::where('enrollment_id', $enrollment->id)
                    ->where('status', 'Draft')
                    ->latest('id')
                    ->first();

                // sudah dibuka kembali dan masih dalam perpanjangan
                if ($draft && $draft->extended_until && $draft->extended_until->copy()->endOfDay()->gte(now())) {
                    continue;
                }

                $late->push([
                    'enrollment' => $enrollment,
                    'deadline'   => $deadline,
                ]);
            }
        }

        return $late;
    }

    /*
    |--------------------------------------------------------------------------
    | Cuti / Pengajuan Kelas
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Sertifikat
    |--------------------------------------------------------------------------
    | ACC -> status Approved, sertifikat tampil di halaman siswa.
    | Tolak -> status Rejected + alasan, guru bisa mengajukan ulang.
    |--------------------------------------------------------------------------
    */

    public function reviewCertificate(Request $request, Certificate $certificate)
    {
        $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'note'     => ['nullable', 'required_if:decision,Rejected', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Isi alasan saat menolak sertifikat.',
        ]);

        if ($certificate->status !== 'Pending') {
            return back()->with('error', 'Sertifikat ini sudah pernah direview.');
        }

        $certificate->update([
            'status'      => $request->decision,
            'review_note' => $request->decision === 'Rejected' ? $request->note : null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', $request->decision === 'Approved'
            ? 'Sertifikat di-ACC dan sekarang tampil di siswa.'
            : 'Sertifikat ditolak dan dikembalikan ke guru.');
    }

    public function reviewLeave(Request $request, TeacherLeave $teacherLeave)
    {
        $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'note'     => ['nullable', 'string', 'max:1000'],
        ]);

        // Kalau ada guru pengganti, dia harus menerima dulu sebelum disetujui
        if ($request->decision === 'Approved'
            && $teacherLeave->replacement_teacher_id
            && $teacherLeave->replacement_response !== 'Accepted') {
            return back()->with('error', 'Guru pengganti belum menerima permintaan ini.');
        }

        $teacherLeave->update([
            'status' => $request->decision,
            'review_note' => $request->decision === 'Rejected' ? $request->note : null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan berhasil ' . ($request->decision === 'Approved' ? 'disetujui' : 'ditolak') . '.');
    }
}