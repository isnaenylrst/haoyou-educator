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

        if ($teacherMaterial->status !== 'Pending') {
            return back()->with('error', 'Pengajuan LP & PPT ini sudah direview.');
        }

        $teacherMaterial->update([
            'status'      => $data['decision'],
            'review_note' => $data['decision'] === 'Rejected' ? $data['note'] : null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'LP & PPT berhasil ' . ($data['decision'] === 'Approved' ? 'di-ACC' : 'dikembalikan ke guru') . '.');
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
                'owner_signature_path'      => $ownerSig,
                'review_note'               => null,
                'returned_at'               => null,
            ]);

            // Regenerate PDF dengan TTD Kurikulum & Owner serta tanggal terbit
            try {
                $e = (object) [
                    'student_name' => $progressReport->student?->name,
                    'class_name'   => $progressReport->enrollment?->class?->class_name,
                ];
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('guru.progress-report-pdf', [
                    'report'  => $progressReport,
                    'teacher' => $progressReport->teacher,
                    'e'       => $e,
                ])->setPaper('a4');

                $path = "progress-reports/report-{$progressReport->id}.pdf";
                Storage::disk('public')->put($path, $pdf->output());
                $progressReport->update(['file_path' => $path]);
            } catch (\Throwable $err) {
                report($err);
            }

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
            return back()->with('error', 'Pengajuan ini sudah direview.');
        }

        if ($data['decision'] === 'Approved'
            && $teacherLeave->replacement_teacher_id
            && $teacherLeave->replacement_response !== 'Accepted') {
            return back()->with('error', 'Guru pengganti belum menerima permintaan ini.');
        }

        $teacherLeave->update([
            'status'      => $data['decision'],
            'review_note' => $data['decision'] === 'Rejected' ? $data['note'] : null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan berhasil ' . ($data['decision'] === 'Approved' ? 'disetujui' : 'ditolak') . '.');
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
            'decision'         => ['required', Rule::in(['Approved', 'Rejected'])],
            'note'             => ['nullable', 'string', 'max:1000'],
            'forward_to_owner' => ['nullable'],
        ]);

        $user = Auth::user();

        if ($data['decision'] === 'Approved') {
            $forwardToOwner = $request->boolean('forward_to_owner');
            $nextStatus = $forwardToOwner ? 'Review Owner' : 'Disetujui';

            $submission->update([
                'status'                    => $nextStatus,
                'curriculum_id'             => $user->id,
                'curriculum_note'           => $data['note'] ?? null,
                'curriculum_approved_at'    => now(),
                'curriculum_signature_path' => $user->signature_path,
            ]);

            if ($submission->teacher?->user) {
                $submission->teacher->user->notify(new AppNotification(
                    title: "Pengajuan {$submission->type} Disetujui",
                    message: "Pengajuan {$submission->item_name} telah disetujui Kurikulum." . ($forwardToOwner ? ' Diteruskan ke Owner untuk verifikasi.' : ' TTD Kurikulum telah ditempel.'),
                    url: url('/guru/pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-circle-check'
                ));
            }

            if ($forwardToOwner) {
                $owners = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Owner'))->get();
                foreach ($owners as $owner) {
                    $owner->notify(new AppNotification(
                        title: "Pengajuan Memerlukan Persetujuan Owner",
                        message: "Pengajuan {$submission->item_name} ({$submission->type}) dari {$submission->teacher?->name} memerlukan persetujuan Owner.",
                        url: url('/owner/pengajuan'),
                        type: 'pengajuan',
                        icon: 'fa-receipt'
                    ));
                }
            }

            return back()->with('success', 'Pengajuan berhasil disetujui' . ($forwardToOwner ? ' dan diteruskan ke Owner.' : '. TTD Kurikulum otomatis ditempel pada dokumen pengajuan.'));
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
        return view('kurikulum.cetak-pengajuan', compact('submission'));
    }
}