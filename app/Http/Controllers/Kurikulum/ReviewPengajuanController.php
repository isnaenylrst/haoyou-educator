<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ClassEnrollment;
use App\Models\ClassSchedule;
use App\Models\ProgressReport;
use App\Models\Submission;
use App\Models\TeacherLeave;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\ProgressReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReviewPengajuanController extends Controller
{
    private const PER_PAGE = 10;
    private const TABS     = ['material', 'jurnal', 'report', 'cuti', 'sertifikat', 'pengajuan'];

    private const DAYS = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    // ------------------------------------------------------------------ HALAMAN

    public function index(Request $request)
    {
        $today     = Carbon::today();
        $todayName = self::DAYS[$today->format('l')];

        // CARD SESI HARI INI (jurnal dicocokkan lewat tanggal sesi, bukan tanggal dibuat)
        $todaySchedules = ClassSchedule::with([
                'class.teacher',
                'class.programPackage.program',
                'teachingJournals' => function ($q) use ($today) {
                    $q->whereDate('session_date', $today->toDateString())
                        ->with(['attendances.student', 'material']);
                },
            ])
            ->whereHas('class', fn ($q) => $q->whereIn('status', ['Open', 'Running']))
            ->where('day', $todayName)
            ->orderBy('start_time')
            ->get();

        $totalSessionToday = $todaySchedules->count();
        $filledToday       = $todaySchedules->filter(fn ($s) => $s->teachingJournals->isNotEmpty())->count();
        $unfilledToday     = $totalSessionToday - $filledToday;

        $teacherNamesToday = $todaySchedules
            ->pluck('class.teacher.name')
            ->filter()
            ->unique()
            ->values();

        // REVIEW DOKUMEN (antrian terlama lebih dulu, dipecah per halaman)
        $page = fn ($query, string $name, string $tab) => $query
            ->paginate(self::PER_PAGE, ['*'], $name)
            ->withQueryString()
            ->appends('tab', $tab);

        $materials = $page(
            TeacherMaterial::with(['teacher', 'material.level', 'consultations'])
                ->where('status', 'Pending')->oldest(),
            'material_page', 'material'
        );

        $journals = $page(
            TeachingJournal::with(['teacher', 'class', 'classSchedule', 'material', 'attendances.student'])
                ->where('status', 'Pending')->oldest(),
            'jurnal_page', 'jurnal'
        );

        $reports = $page(
            ProgressReport::with(['student', 'teacher', 'enrollment.class'])
                ->where('status', 'Submitted')->whereNull('reviewed_at')->oldest(),
            'report_page', 'report'
        );

        $leaves = $page(
            TeacherLeave::with(['teacher', 'classSchedule.class', 'replacementTeacher'])
                ->where('status', 'Pending')->oldest(),
            'cuti_page', 'cuti'
        );

        $certificates = $page(
            Certificate::with(['student', 'teacher', 'enrollment.class'])->pendingReview()->oldest(),
            'sertifikat_page', 'sertifikat'
        );

        $submissions = $page(
            Submission::with(['teacher', 'creator'])->whereIn('status', ['Diajukan', 'Review Kurikulum'])->oldest(),
            'submission_page', 'pengajuan'
        );

        $lateEnrollments = $this->lateEnrollments();

        $totals = [
            'material'   => $materials->total(),
            'jurnal'     => $journals->total(),
            'report'     => $reports->total() + $lateEnrollments->count(),
            'cuti'       => $leaves->total(),
            'sertifikat' => $certificates->total(),
            'pengajuan'  => $submissions->total(),
        ];

        $totalPending = $materials->total() + $journals->total() + $reports->total()
            + $leaves->total() + $certificates->total() + $submissions->total();

        // Tab aktif: yang diminta (?tab=), atau tab pertama yang punya antrian
        $requested = $request->query('tab');
        $tab = in_array($requested, self::TABS, true)
            ? $requested
            : (collect($totals)->filter(fn ($n) => $n > 0)->keys()->first() ?? 'material');

        return view('kurikulum.review-pengajuan', compact(
            'todaySchedules', 'totalSessionToday', 'filledToday', 'unfilledToday', 'teacherNamesToday',
            'materials', 'journals', 'reports', 'leaves', 'certificates', 'submissions', 'lateEnrollments',
            'totals', 'totalPending', 'tab'
        ));
    }

    // ------------------------------------------------------------------ BERKAS (aman, butuh login)

        public function file(string $type, int $id)
    {
        if ($type === 'report') {
            return redirect()->route('progress-report.document.pdf', ProgressReport::findOrFail($id));
        }
        $path = match ($type) {
            'lp'     => TeacherMaterial::findOrFail($id)->lp_file_path,
            'ppt'    => TeacherMaterial::findOrFail($id)->ppt_file_path,
            'report' => ProgressReport::findOrFail($id)->file_path,
            'cert'    => Certificate::findOrFail($id)->file_path,
            'cuti'    => TeacherLeave::findOrFail($id)->supporting_document,
            'receipt' => Submission::findOrFail($id)->receipt_path,
            default   => abort(404),
        };

        abort_unless($path && Storage::disk('public')->exists($path), 404, 'Berkas tidak ditemukan.');

        $full = storage_path('app/public/' . $path);
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // PDF dan gambar dibuka di browser; dokumen lain (PPT/DOC) diunduh
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            return response()->file($full);
        }

        return response()->download($full, Str::slug($type . '-' . $id) . '.' . $ext);
    }

    // ------------------------------------------------------------------ SESI (card hari ini)

    public function ackSession(TeachingJournal $teachingJournal)
    {
        if ($teachingJournal->status !== 'Pending') {
            return back()->with('error', 'Sesi ini sudah direview.');
        }

        $teachingJournal->update([
            'status'      => 'Reviewed',
            'review_note' => null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Sesi berhasil di-ACC.');
    }

    // ------------------------------------------------------------------ LP & PPT

    public function reviewMaterial(Request $request, TeacherMaterial $teacherMaterial)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'note'     => ['nullable', 'required_if:decision,Rejected', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Isi alasan saat mengembalikan LP & PPT agar guru tahu apa yang harus diperbaiki.',
        ]);

        if (!in_array($teacherMaterial->status, ['Pending', 'Diajukan'])) {
            return back()->with('error', 'Pengajuan LP & PPT ini sudah direview.');
        }

        $finalStatus = $data['decision'] === 'Approved' ? 'Disetujui' : 'Perlu Revisi';

        DB::transaction(function () use ($teacherMaterial, $data, $finalStatus) {
            $teacherMaterial->update([
                'status'      => $finalStatus,
                'review_note' => $data['decision'] === 'Rejected' ? $data['note'] : null,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Update catatan review pada versi dokumen terbaru
            $latestVersion = $teacherMaterial->versions()->latest('version_number')->first();
            if ($latestVersion) {
                $latestVersion->update([
                    'status'      => $finalStatus,
                    'review_note' => $data['decision'] === 'Rejected' ? $data['note'] : null,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);
            }

            // Notifikasi dua arah ke Guru bersangkutan
            $teacher = $teacherMaterial->teacher;
            if ($teacher?->user) {
                $className = $teacherMaterial->class?->class_name ?? 'Kelas';
                $isApproved = $data['decision'] === 'Approved';

                $teacher->user->notify(new AppNotification(
                    title: $isApproved ? "Dokumen Kelas Disetujui: {$className}" : "Dokumen Kelas Perlu Revisi: {$className}",
                    message: $isApproved
                        ? "Kurikulum telah menyetujui Lesson Plan & PPT untuk kelas {$className}."
                        : "Kurikulum meminta perbaikan LP & PPT kelas {$className}. Catatan: {$data['note']}",
                    url: url('/guru/kelas'),
                    type: 'dokumen_kelas',
                    icon: $isApproved ? 'fa-circle-check' : 'fa-circle-exclamation'
                ));
            }
        });

        return back()->with('success', 'LP & PPT berhasil ' . ($data['decision'] === 'Approved' ? 'di-ACC' : 'dikembalikan ke guru untuk revisi') . '.');
    }

    // ------------------------------------------------------------------ JURNAL ONLINE

    public function reviewJournal(Request $request, TeachingJournal $teachingJournal)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['Reviewed', 'Revision'])],
            'note'     => ['nullable', 'required_if:decision,Revision', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Isi alasan saat mengembalikan jurnal agar guru tahu apa yang harus diperbaiki.',
        ]);

        if ($teachingJournal->status !== 'Pending') {
            return back()->with('error', 'Jurnal ini sudah direview.');
        }

        $teachingJournal->update([
            'status'      => $data['decision'],
            'review_note' => $data['decision'] === 'Revision' ? $data['note'] : null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Jurnal berhasil ' . ($data['decision'] === 'Reviewed' ? 'di-ACC' : 'dikembalikan untuk revisi') . '.');
    }

    // ------------------------------------------------------------------ PROGRESS REPORT
    // ACC -> reviewed_at terisi (status tetap Submitted, tampil di siswa)
    // Kembalikan -> status Draft + alasan, guru punya waktu GRACE_DAYS hari

    public function reviewReport(Request $request, ProgressReport $progressReport)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'note'     => ['nullable', 'required_if:decision,Rejected', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Isi alasan saat mengembalikan progress report ke guru.',
        ]);

        if ($progressReport->status !== 'Submitted' || $progressReport->reviewed_at) {
            return back()->with('error', 'Progress report ini sudah direview atau sedang diperbaiki guru.');
        }

        if ($data['decision'] === 'Approved') {
            $user = Auth::user();
            $ownerSig = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Owner'))
                ->whereNotNull('signature_path')
                ->value('signature_path');

            $progressReport->update([
                'status'                    => 'Published',
                'reviewed_by'               => $user->id,
                'reviewed_at'               => now(),
                'published_at'              => now(),
                'curriculum_signature_path' => $user->signature_path,
                'owner_signature_path'      => null, // TTD Owner baru ditempel saat Owner menandatangani di Antrean TTD
                'review_note'               => null,
                'returned_at'               => null,
            ]);

                \App\Services\ProgressReportDocument::store($progressReport->refresh());

            // Notifikasi ke Siswa
            if ($progressReport->student?->user) {
                $progressReport->student->user->notify(new AppNotification(
                    title: "Progress Report Telah Terbit",
                    message: "Laporan perkembangan belajar Anda untuk kelas " . ($progressReport->enrollment?->class?->class_name ?? '') . " telah resmi diterbitkan oleh Kurikulum.",
                    url: url('/progress-report'),
                    type: 'progress_report',
                    icon: 'fa-chart-column'
                ));
            }

            // Notifikasi ke Guru
            if ($progressReport->teacher?->user) {
                $progressReport->teacher->user->notify(new AppNotification(
                    title: "Progress Report Di-ACC",
                    message: "Progress Report untuk siswa " . ($progressReport->student?->name ?? '') . " telah di-ACC dan terbit.",
                    url: url('/guru/progress-report'),
                    type: 'progress_report',
                    icon: 'fa-circle-check'
                ));
            }
        } else {
            $progressReport->update([
                'status'         => 'Draft',
                'reviewed_by'    => null,
                'reviewed_at'    => null,
                'returned_at'    => now(),
                'review_note'    => $data['note'],
                'extended_until' => now()->addDays(ProgressReportService::GRACE_DAYS)->toDateString(),
            ]);
        }

        return back()->with('success', 'Progress report berhasil '
            . ($data['decision'] === 'Approved'
                ? 'di-ACC dan sekarang tampil di siswa'
                : 'dikembalikan ke guru (batas perbaikan ' . ProgressReportService::GRACE_DAYS . ' hari)') . '.');
    }

    /** Buka kembali progress report yang lewat batas H+3 (waktu tambahan). */
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

        return back()->with('success', 'Progress report dibuka kembali. Guru punya waktu ' . ProgressReportService::GRACE_DAYS . ' hari untuk mengisi.');
    }

    // ------------------------------------------------------------------ CUTI / GANTI KELAS

    public function reviewLeave(Request $request, TeacherLeave $teacherLeave)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'note'     => ['nullable', 'required_if:decision,Rejected', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Isi alasan saat menolak pengajuan.',
        ]);

        if ($teacherLeave->status !== 'Pending') {
            return back()->with('error', 'Pengajuan ini sudah direview secara final.');
        }

        $teacherLeave->load(['teacher.user', 'replacementTeacher.user', 'classSchedule.class']);
        $className = $teacherLeave->classSchedule?->class?->class_name ?? 'Kelas';
        $currUser = Auth::user();

        if ($data['decision'] === 'Approved') {
            $repAccepted = empty($teacherLeave->replacement_teacher_id) || $teacherLeave->replacement_response === 'Accepted';
            $finalStatus = $repAccepted ? 'Approved' : 'Pending';

            $teacherLeave->update([
                'curriculum_response' => 'Accepted',
                'status'              => $finalStatus,
                'review_note'         => $data['note'] ?? null,
                'approved_by'         => $currUser->id,
                'approved_at'         => now(),
            ]);

            // Notifikasi ke guru pemohon
            if ($teacherLeave->teacher?->user) {
                $msg = $repAccepted
                    ? "Pengajuan {$teacherLeave->request_type} untuk {$className} telah disetujui penuh."
                    : "Kurikulum telah menyetujui pengajuan {$teacherLeave->request_type} untuk {$className}. Menunggu konfirmasi penerimaan dari guru pengganti.";

                $teacherLeave->teacher->user->notify(new AppNotification(
                    title: "Persetujuan {$teacherLeave->request_type}: {$className}",
                    message: $msg,
                    url: url('/guru/cuti'),
                    type: 'cuti',
                    icon: 'fa-circle-check'
                ));
            }

            return back()->with('success', 'Persetujuan Kurikulum berhasil disimpan.' . ($repAccepted ? ' Pengajuan telah disetujui penuh.' : ' Menunggu konfirmasi dari guru pengganti.'));
        }

        // Jika Ditolak Kurikulum
        $teacherLeave->update([
            'curriculum_response' => 'Rejected',
            'status'              => 'Rejected',
            'review_note'         => $data['note'] ?? 'Ditolak oleh Kurikulum',
            'approved_by'         => $currUser->id,
            'approved_at'         => now(),
        ]);

        if ($teacherLeave->teacher?->user) {
            $teacherLeave->teacher->user->notify(new AppNotification(
                title: "Pengajuan {$teacherLeave->request_type} Ditolak",
                message: "Pengajuan {$teacherLeave->request_type} untuk {$className} ditolak Kurikulum. Alasan: " . ($data['note'] ?? '-'),
                url: url('/guru/cuti'),
                type: 'cuti',
                icon: 'fa-circle-xmark'
            ));
        }

        return back()->with('success', 'Pengajuan berhasil ditolak oleh Kurikulum.');
    }

    // ------------------------------------------------------------------ SERTIFIKAT

    public function reviewCertificate(Request $request, Certificate $certificate)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'note'     => ['nullable', 'required_if:decision,Rejected', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Isi alasan saat menolak sertifikat.',
        ]);

        if ($certificate->status !== 'Pending') {
            return back()->with('error', 'Sertifikat ini sudah pernah direview.');
        }

        $certificate->update([
            'status'      => $data['decision'],
            'review_note' => $data['decision'] === 'Rejected' ? $data['note'] : null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', $data['decision'] === 'Approved'
            ? 'Sertifikat di-ACC dan sekarang tampil di siswa.'
            : 'Sertifikat ditolak dan dikembalikan ke guru.');
    }

    // ------------------------------------------------------------------ HELPER

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

                if ($draft && $draft->extended_until && $draft->extended_until->copy()->endOfDay()->gte(now())) {
                    continue;
                }

                $late->push(['enrollment' => $enrollment, 'deadline' => $deadline]);
            }
        }

        return $late;
    }

    // ------------------------------------------------------------------ PENGAJUAN (REIMBURSE & PEMBELIAN)

    public function reviewSubmission(Request $request, Submission $submission)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'note'     => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();

        if ($data['decision'] === 'Approved') {
            // Sesuai alur Poin 4: Kurikulum + TTD -> diteruskan ke Admin untuk verifikasi
            $submission->update([
                'status'                    => 'Review Admin',
                'curriculum_id'             => $user->id,
                'curriculum_note'           => $data['note'] ?? null,
                'curriculum_approved_at'    => now(),
                'curriculum_signature_path' => $user->signature_path,
            ]);

            if ($submission->teacher?->user) {
                $submission->teacher->user->notify(new AppNotification(
                    title: "Pengajuan {$submission->type} Disetujui Kurikulum",
                    message: "Pengajuan {$submission->item_name} telah disetujui Kurikulum (+ TTD) dan diteruskan ke Admin untuk verifikasi pembayaran.",
                    url: url('/guru/pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-circle-check'
                ));
            }

            // Notifikasi ke seluruh tim Admin
            $admins = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Admin'))->get();
            foreach ($admins as $admin) {
                $admin->notify(new AppNotification(
                    title: "Verifikasi Pengajuan Reimburse: {$submission->item_name}",
                    message: "Kurikulum telah menyetujui pengajuan {$submission->type} dari {$submission->teacher?->name} sebesar {$submission->formatted_amount}. Silakan diverifikasi.",
                    url: url('/admin/pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-receipt'
                ));
            }

            return back()->with('success', 'Pengajuan berhasil disetujui Kurikulum, TTD telah ditempel, dan berkas diteruskan ke Admin untuk verifikasi.');
        }

        $submission->update([
            'status'          => 'Ditolak',
            'curriculum_id'   => $user->id,
            'curriculum_note' => $data['note'] ?? 'Pengajuan ditolak oleh Kurikulum',
        ]);

        if ($submission->teacher?->user) {
            $submission->teacher->user->notify(new AppNotification(
                title: "Pengajuan {$submission->type} Ditolak",
                message: "Pengajuan {$submission->item_name} ditolak Kurikulum. Catatan: " . ($data['note'] ?? '-'),
                url: url('/guru/pengajuan'),
                type: 'pengajuan',
                icon: 'fa-circle-xmark'
            ));
        }

        return back()->with('success', 'Pengajuan telah ditolak.');
    }

    /** Cetak / preview dokumen pengajuan dengan TTD Kurikulum & Owner */
    public function printSubmission(Submission $submission)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $submission);

        return view('kurikulum.cetak-pengajuan', compact('submission'));
    }

    /** Buka kunci pengisian jurnal yang terlambat (> 24 jam) untuk guru */
    public function unlockJournal(Request $request)
    {
        $request->validate([
            'class_schedule_id' => ['required', 'exists:class_schedules,id'],
            'session_date'      => ['required', 'date'],
        ]);

        \App\Models\ScheduleJournalUnlock::updateOrCreate(
            [
                'class_schedule_id' => $request->class_schedule_id,
                'session_date'      => $request->session_date,
            ],
            [
                'unlocked_until'    => now()->addHours(24),
                'unlocked_by'       => Auth::id(),
            ]
        );

        $schedule = \App\Models\ClassSchedule::with('class.teacher.user')->find($request->class_schedule_id);
        if ($schedule?->class?->teacher?->user) {
            $schedule->class->teacher->user->notify(new \App\Notifications\AppNotification(
                title: "Kunci Jurnal Dibuka Kurikulum",
                message: "Kurikulum telah membuka kunci pengisian jurnal untuk kelas {$schedule->class->class_name} pada tanggal " . \Illuminate\Support\Carbon::parse($request->session_date)->translatedFormat('d M Y') . ". Silakan isi jurnal dalam 24 jam ke depan.",
                url: url('/guru/attendance?date=' . $request->session_date),
                type: 'jurnal',
                icon: 'fa-lock-open'
            ));
        }

        return back()->with('success', 'Kunci pengisian jurnal berhasil dibuka untuk 24 jam ke depan, dan guru telah diberi notifikasi.');
    }
}