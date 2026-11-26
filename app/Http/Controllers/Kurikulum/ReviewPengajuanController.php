<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ClassEnrollment;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ProgressReport;
use App\Models\ScheduleJournalUnlock;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\TeacherLeave;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\JournalStatusResolver;
use App\Services\MissingJournalFinder;
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

    // ------------------------------------------------------------------ FILTER

    /**
     * Filter bersama untuk semua tab Review Pengajuan.
     *  - guru   : id guru
     *  - bulan  : YYYY-MM (jika diisi, dipakai lebih dulu daripada dari/sampai)
     *  - dari / sampai : rentang tanggal (Y-m-d), boleh salah satu saja
     * Khusus tab Jurnal masih ada jurnal_late (1 = terlambat mengisi, 0 = tepat waktu).
     */
    private function reviewFilters(Request $request): array
    {
        $teacher = $request->integer('guru') ?: ($request->integer('jurnal_teacher') ?: null);

        $from = $to = null;
        $month = (string) $request->query('bulan', '');

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $from = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
            $to   = $from->copy()->endOfMonth()->startOfDay();
        } else {
            $month = '';
            $parse = function (?string $value): ?Carbon {
                if (! $value || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return null;
                }
                try {
                    return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
                } catch (\Throwable $e) {
                    return null;
                }
            };
            $from = $parse($request->query('dari'));
            $to   = $parse($request->query('sampai'));

            // dari > sampai: tukar saja supaya tidak kosong
            if ($from && $to && $from->gt($to)) {
                [$from, $to] = [$to, $from];
            }
        }

        $late = in_array($request->query('jurnal_late'), ['1', '0'], true)
            ? (string) $request->query('jurnal_late')
            : null;

        return [
            'teacher' => $teacher,
            'month'   => $month,
            'from'    => $from,
            'to'      => $to,
            'late'    => $late,
            'active'  => (bool) ($teacher || $from || $to),
        ];
    }

    /** Terapkan filter guru + tanggal ke satu query (kolom tanggal berbeda per tab). */
    private function applyReviewFilters($query, array $f, string $dateColumn, string $teacherColumn = 'teacher_id')
    {
        return $query
            ->when($f['teacher'], fn ($q) => $q->where($teacherColumn, $f['teacher']))
            ->when($f['from'], fn ($q) => $q->whereDate($dateColumn, '>=', $f['from']->toDateString()))
            ->when($f['to'], fn ($q) => $q->whereDate($dateColumn, '<=', $f['to']->toDateString()));
    }

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

        // Filter bersama semua tab: guru + bulan / rentang tanggal (jurnal masih ada tepat waktu / terlambat)
        $filters           = $this->reviewFilters($request);
        $filterActive      = $filters['active'];
        $journalTeacherId  = $filters['teacher'];
        $journalLateFilter = $filters['late'];

        $materials = $page(
            $this->applyReviewFilters(
                TeacherMaterial::with(['teacher', 'material.level', 'consultations'])->where('status', 'Pending'),
                $filters, 'created_at'
            )->oldest(),
            'material_page', 'material'
        );

        // Jurnal: guru bisa pengisi utama ATAU pengganti (filled_by); tanggal = tanggal sesi
        $journalBase = TeachingJournal::where('status', 'Pending')
            ->when($journalTeacherId, fn ($q) => $q->where(
                fn ($w) => $w->where('teacher_id', $journalTeacherId)->orWhere('filled_by', $journalTeacherId)
            ))
            ->when($filters['from'], fn ($q) => $q->whereDate('session_date', '>=', $filters['from']->toDateString()))
            ->when($filters['to'], fn ($q) => $q->whereDate('session_date', '<=', $filters['to']->toDateString()));

        $journals = $page(
            (clone $journalBase)
                ->with(['teacher', 'class', 'classSchedule', 'material', 'attendances.student'])
                ->when($journalLateFilter !== null, fn ($q) => $q->where('is_late', $journalLateFilter === '1'))
                ->oldest(),
            'jurnal_page', 'jurnal'
        );

        // Hitungan tab jurnal mengikuti guru/tanggal, tetapi tidak ikut filter tepat waktu/terlambat
        $pendingJournalCount = (clone $journalBase)->count();

        // Daftar laoshi yang terlambat mengisi + jumlahnya (60 hari terakhir, atau sesuai filter tanggal)
        $lateFillers = TeachingJournal::query()
            ->where('is_late', true)
            ->when($filters['from'] || $filters['to'],
                function ($q) use ($filters) {
                    $q->when($filters['from'], fn ($w) => $w->whereDate('session_date', '>=', $filters['from']->toDateString()))
                      ->when($filters['to'], fn ($w) => $w->whereDate('session_date', '<=', $filters['to']->toDateString()));
                },
                fn ($q) => $q->whereDate('session_date', '>=', now()->subDays(60)->toDateString())
            )
            ->selectRaw('teacher_id, COUNT(*) as total')
            ->groupBy('teacher_id')
            ->orderByDesc('total')
            ->get();
        $lateFillerNames = Teacher::whereIn('id', $lateFillers->pluck('teacher_id'))->pluck('name', 'id');
        $lateFillers = $lateFillers->map(fn ($r) => (object) [
            'teacher_id' => $r->teacher_id,
            'name'       => $lateFillerNames[$r->teacher_id] ?? '-',
            'total'      => $r->total,
        ]);
        $journalTeachers = Teacher::where('status', 'Active')->orderBy('name')->get(['id', 'name']);

        // Progress report: tanggal = tanggal dikirim guru
        $reports = $page(
            $this->applyReviewFilters(
                ProgressReport::with(['student', 'teacher', 'enrollment.class'])
                    ->where('status', 'Submitted')->whereNull('reviewed_at'),
                $filters, 'submitted_at'
            )->oldest(),
            'report_page', 'report'
        );

        // Cuti: tanggal = tanggal kelas yang diminta cuti (leave_date)
        $leaves = $page(
            $this->applyReviewFilters(
                TeacherLeave::with(['teacher', 'classSchedule.class', 'replacementTeacher'])->where('status', 'Pending'),
                $filters, 'leave_date'
            )->oldest(),
            'cuti_page', 'cuti'
        );

        // Sertifikat: tanggal = tanggal dibuat/diajukan
        $certificates = $page(
            $this->applyReviewFilters(
                Certificate::with(['student', 'teacher', 'enrollment.class'])->pendingReview(),
                $filters, 'created_at'
            )->oldest(),
            'sertifikat_page', 'sertifikat'
        );

        // Reimburse & bahan: tanggal = tanggal pengajuan (kolom date)
        $submissions = $page(
            $this->applyReviewFilters(
                Submission::with(['teacher', 'creator'])->whereIn('status', ['Diajukan', 'Review Kurikulum']),
                $filters, 'date'
            )->oldest(),
            'submission_page', 'pengajuan'
        );

        // Daftar terlambat (report belum dikirim / jurnal terkunci) ikut filter guru + tanggal
        $lateEnrollments = $this->lateEnrollments()
            ->filter(function ($row) use ($filters) {
                $teacherId = $row['enrollment']->class?->teacher_id;
                $deadline  = $row['deadline']->copy()->startOfDay();

                return (! $filters['teacher'] || (int) $teacherId === (int) $filters['teacher'])
                    && (! $filters['from'] || $deadline->gte($filters['from']))
                    && (! $filters['to'] || $deadline->lte($filters['to']));
            })
            ->values();

        $lateJournals = $this->lateJournals()
            ->filter(function ($item) use ($filters) {
                $date = $item->session_date->copy()->startOfDay();

                return (! $filters['teacher'] || (int) $item->teacher?->id === (int) $filters['teacher'])
                    && (! $filters['from'] || $date->gte($filters['from']))
                    && (! $filters['to'] || $date->lte($filters['to']));
            })
            ->values();

        $totals = [
            'material'   => $materials->total(),
            'jurnal'     => $pendingJournalCount + $lateJournals->count(),
            'report'     => $reports->total() + $lateEnrollments->count(),
            'cuti'       => $leaves->total(),
            'sertifikat' => $certificates->total(),
            'pengajuan'  => $submissions->total(),
        ];

        $totalPending = $materials->total() + $pendingJournalCount + $lateJournals->count() + $reports->total()
            + $leaves->total() + $certificates->total() + $submissions->total();

        // Tab aktif: yang diminta (?tab=), atau tab pertama yang punya antrian
        $requested = $request->query('tab');
        $tab = in_array($requested, self::TABS, true)
            ? $requested
            : (collect($totals)->filter(fn ($n) => $n > 0)->keys()->first() ?? 'material');

        return view('kurikulum.review-pengajuan', compact(
            'todaySchedules', 'totalSessionToday', 'filledToday', 'unfilledToday', 'teacherNamesToday',
            'materials', 'journals', 'reports', 'leaves', 'certificates', 'submissions', 'lateEnrollments', 'lateJournals',
            'totals', 'totalPending', 'tab',
            'lateFillers', 'journalTeachers', 'journalTeacherId', 'journalLateFilter', 'filters', 'filterActive'
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

            if (empty($user->signature_path) || !Storage::disk('public')->exists($user->signature_path)) {
                return redirect()->route('profil.index')
                    ->with('error', 'Anda harus memiliki tanda tangan digital terlebih dahulu sebelum melakukan ACC. Silakan gambar atau unggah tanda tangan Anda di menu Profil & Tanda Tangan.');
            }

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
                // TTD guru dibubuhkan ulang saat guru mengirim kembali setelah perbaikan
                'teacher_signature_path'    => null,
                'teacher_signed_at'         => null,
                'curriculum_signature_path' => null,
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

            // Notifikasi ke guru pengganti jika ada
            if ($teacherLeave->replacementTeacher?->user) {
                $teacherLeave->replacementTeacher->user->notify(new AppNotification(
                    title: "Persetujuan {$teacherLeave->request_type}: {$className}",
                    message: "Pengajuan {$teacherLeave->request_type} untuk {$className} (" . ($teacherLeave->teacher?->name ?? 'Guru') . ") telah disetujui oleh Kurikulum.",
                    url: url('/guru/schedule'),
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

        // Notifikasi ke guru pengganti jika ada
        if ($teacherLeave->replacementTeacher?->user) {
            $teacherLeave->replacementTeacher->user->notify(new AppNotification(
                title: "Pengajuan {$teacherLeave->request_type} Ditolak",
                message: "Pengajuan {$teacherLeave->request_type} untuk {$className} (" . ($teacherLeave->teacher?->name ?? 'Guru') . ") ditolak Kurikulum.",
                url: url('/guru/schedule'),
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

        if ($data['decision'] === 'Approved') {
            $user = Auth::user();
            if (empty($user->signature_path) || !Storage::disk('public')->exists($user->signature_path)) {
                return redirect()->route('profil.index')
                    ->with('error', 'Anda harus memiliki tanda tangan digital terlebih dahulu sebelum melakukan ACC. Silakan gambar atau unggah tanda tangan Anda di menu Profil & Tanda Tangan.');
            }
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

    /**
     * Sesi mengajar yang terlambat (> 24 jam) dan belum diisi jurnalnya,
     * termasuk permohonan buka kunci yang dikirim oleh guru.
     *
     * Sumber data digabung supaya SAMA dengan yang dilihat guru:
     *  1. slot jadwal mingguan (MissingJournalFinder), kecuali slot yang sesinya sudah dipindah (reschedule);
     *  2. sesi nyata di tabel class_sessions (tanggal hasil reschedule / kelas private);
     *  3. permohonan buka kunci berstatus Pending yang tanggalnya belum tercakup di atas.
     * Kunci unlock = (class_schedule_id, session_date), jadi tanggal di sini HARUS sama dengan tanggal di sisi guru.
     */
    private function lateJournals()
    {
        $from  = now()->subDays(60)->startOfDay();
        $today = now()->startOfDay();
        $fromStr  = $from->toDateString();
        $todayStr = $today->toDateString();

        // 1. Slot dari jadwal mingguan
        $generated = app(MissingJournalFinder::class)->find($from, now());

        // Slot mingguan yang sesinya sudah dipindah ke tanggal lain -> jangan ditampilkan lagi
        $movedOut = ClassSession::where('status', '!=', 'Cancelled')
            ->whereNotNull('original_date')
            ->whereColumn('session_date', '!=', 'original_date')
            ->whereDate('original_date', '>=', $fromStr)
            ->get(['class_id', 'original_date'])
            ->map(fn ($s) => $s->class_id . '|' . $s->original_date->format('Y-m-d'))
            ->flip();

        $items = $generated
            ->reject(fn ($i) => $movedOut->has($i->class->id . '|' . $i->date->format('Y-m-d')))
            ->map(fn ($i) => (object) [
                'schedule'      => $i->schedule,
                'class'         => $i->class,
                'teacher'       => $i->teacher,
                'is_substitute' => $i->is_substitute,
                'session_date'  => $i->date->copy(),
                'days_late'     => $i->days_late,
            ])
            ->values();

        $keyOf = fn ($scheduleId, $date) => $scheduleId . '|' . Carbon::parse($date)->format('Y-m-d');
        $keys  = $items->mapWithKeys(fn ($i) => [$keyOf($i->schedule->id, $i->session_date) => true]);

        // Jurnal yang sudah terisi (untuk sesi nyata & permohonan)
        $journals = TeachingJournal::whereBetween('session_date', [$fromStr, $todayStr])
            ->get(['class_session_id', 'class_schedule_id', 'session_date']);
        $doneBySession  = $journals->whereNotNull('class_session_id')->pluck('class_session_id')->flip();
        $doneBySchedule = $journals->mapWithKeys(fn ($j) => [$keyOf($j->class_schedule_id, $j->session_date) => true]);

        // Cuti yang disetujui (guru pengganti yang wajib mengisi)
        $leaves = TeacherLeave::with('replacementTeacher')
            ->where('status', 'Approved')
            ->whereBetween('leave_date', [$fromStr, $todayStr])
            ->get()
            ->keyBy(fn ($l) => $keyOf($l->class_schedule_id, $l->leave_date));

        // 2. Sesi nyata dari class_sessions yang belum tercakup slot mingguan
        $sessions = ClassSession::with(['schedule', 'class.teacher'])
            ->whereIn('status', ['Scheduled', 'Completed'])
            ->whereNotNull('class_schedule_id')
            ->whereDate('session_date', '>=', $fromStr)
            ->whereDate('session_date', '<=', $todayStr)
            ->get()
            ->filter(fn ($s) => $s->schedule && $s->class && $s->class->teacher);

        $sessionByKey = $sessions->keyBy(fn ($s) => $keyOf($s->class_schedule_id, $s->session_date));

        foreach ($sessions as $s) {
            $key = $keyOf($s->class_schedule_id, $s->session_date);
            if ($keys->has($key) || $doneBySession->has($s->id) || $doneBySchedule->has($key)) {
                continue;
            }

            $teacher = $s->class->teacher;
            $isSub   = false;
            if ($leave = $leaves->get($key)) {
                if (! $leave->replacementTeacher) {
                    continue; // cuti tanpa pengganti: tidak ada kewajiban jurnal
                }
                $teacher = $leave->replacementTeacher;
                $isSub   = true;
            }

            $sch = clone $s->schedule;           // pakai jam sesi nyata, bukan jam jadwal mingguan
            $sch->start_time = $s->start_time;
            $sch->end_time   = $s->end_time;

            $items->push((object) [
                'schedule'      => $sch,
                'class'         => $s->class,
                'teacher'       => $teacher,
                'is_substitute' => $isSub,
                'session_date'  => $s->session_date->copy(),
                'days_late'     => (int) $s->session_date->copy()->startOfDay()->diffInDays($today, true),
            ]);
            $keys->put($key, true);
        }

        // 3. Permohonan Pending yang tanggalnya belum tercakup
        $pending = ScheduleJournalUnlock::with('schedule.class.teacher')
            ->where('status', 'Pending')
            ->whereDate('session_date', '>=', $fromStr)
            ->get();

        foreach ($pending as $u) {
            $key = $keyOf($u->class_schedule_id, $u->session_date);
            if ($keys->has($key) || $doneBySchedule->has($key) || ! $u->schedule || ! $u->schedule->class) {
                continue;
            }

            $sch = clone $u->schedule;
            if ($cs = $sessionByKey->get($key)) {
                $sch->start_time = $cs->start_time;
                $sch->end_time   = $cs->end_time;
            }
            $sch->setRelation('class', $u->schedule->class);

            $items->push((object) [
                'schedule'      => $sch,
                'class'         => $u->schedule->class,
                'teacher'       => $u->schedule->class->teacher,
                'is_substitute' => false,
                'session_date'  => $u->session_date->copy(),
                'days_late'     => (int) $u->session_date->copy()->startOfDay()->diffInDays($today, true),
            ]);
            $keys->put($key, true);
        }

        if ($items->isEmpty()) {
            return collect();
        }

        // Unlock records (kunci sama persis dengan yang dipakai sisi guru)
        $unlocks = ScheduleJournalUnlock::with(['requester', 'unlocker'])
            ->whereIn('class_schedule_id', $items->pluck('schedule.id')->unique()->values())
            ->whereDate('session_date', '>=', $fromStr)
            ->get()
            ->keyBy(fn ($u) => $keyOf($u->class_schedule_id, $u->session_date));

        return $items->map(function ($item) use ($unlocks, $keyOf) {
                $unlock   = $unlocks->get($keyOf($item->schedule->id, $item->session_date));
                $deadline = JournalStatusResolver::calculateDeadline(
                    $item->session_date->format('Y-m-d'),
                    (string) $item->schedule->end_time
                );

                $item->deadline      = $deadline;
                $item->is_past       = now()->gt($deadline);
                $item->unlock_record = $unlock;
                $item->is_unlocked   = (bool) ($unlock && $unlock->is_open);
                $item->has_request   = (bool) ($unlock && $unlock->status === 'Pending');

                return $item;
            })
            ->filter(fn ($item) => $item->is_past && $item->teacher)   // hanya yang lewat batas 24 jam
            ->sort(fn ($a, $b) => [(int) $b->has_request, $b->session_date->timestamp, $a->schedule->start_time]
                <=> [(int) $a->has_request, $a->session_date->timestamp, $b->schedule->start_time])
            ->values();
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
            if (empty($user->signature_path) || !Storage::disk('public')->exists($user->signature_path)) {
                return redirect()->route('profil.index')
                    ->with('error', 'Anda harus memiliki tanda tangan digital terlebih dahulu sebelum melakukan ACC. Silakan gambar atau unggah tanda tangan Anda di menu Profil & Tanda Tangan.');
            }

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

        $unlock = ScheduleJournalUnlock::updateOrCreate(
            [
                'class_schedule_id' => $request->class_schedule_id,
                'session_date'      => $request->session_date,
            ],
            [
                'status'         => 'Unlocked',
                'unlocked_until' => now()->addHours(24),
                'unlocked_by'    => Auth::id(),
                'rejection_note' => null,
            ]
        );

        $schedule = ClassSchedule::with('class.teacher.user')->find($request->class_schedule_id);

        // Utamakan pemohon (bisa guru pengganti); kalau tidak ada, guru kelas
        $recipient = $unlock->requester ?? $schedule?->class?->teacher?->user;
        if ($recipient) {
            $recipient->notify(new AppNotification(
                title: "Kunci Jurnal Dibuka Kurikulum",
                message: "Kurikulum telah membuka kunci pengisian jurnal untuk kelas {$schedule->class->class_name} pada tanggal " . Carbon::parse($request->session_date)->translatedFormat('d M Y') . ". Silakan isi jurnal dalam 24 jam ke depan.",
                url: url('/guru/attendance?date=' . Carbon::parse($request->session_date)->format('Y-m-d')),
                type: 'jurnal',
                icon: 'fa-lock-open'
            ));
        }

        return back()->with('success', 'Kunci pengisian jurnal berhasil dibuka untuk 24 jam ke depan, dan guru telah diberi notifikasi.');
    }

    /** Tolak permohonan buka kunci jurnal yang diajukan guru */
    public function rejectUnlockJournal(Request $request)
    {
        $request->validate([
            'class_schedule_id' => ['required', 'exists:class_schedules,id'],
            'session_date'      => ['required', 'date'],
            'rejection_note'    => ['required', 'string', 'max:1000'],
        ], [
            'rejection_note.required' => 'Alasan penolakan pembukaan kunci wajib diisi.',
        ]);

        $unlock = ScheduleJournalUnlock::updateOrCreate(
            [
                'class_schedule_id' => $request->class_schedule_id,
                'session_date'      => $request->session_date,
            ],
            [
                'status'         => 'Rejected',
                'rejection_note' => $request->rejection_note,
                'unlocked_until' => null,
                'unlocked_by'    => Auth::id(),
            ]
        );

        $schedule  = ClassSchedule::with('class.teacher.user')->find($request->class_schedule_id);
        $recipient = $unlock->requester ?? $schedule?->class?->teacher?->user;
        if ($recipient) {
            $recipient->notify(new AppNotification(
                title: "Permohonan Buka Kunci Jurnal Ditolak",
                message: "Permohonan buka kunci jurnal untuk kelas {$schedule->class->class_name} pada tanggal " . Carbon::parse($request->session_date)->translatedFormat('d M Y') . " ditolak Kurikulum. Alasan: {$request->rejection_note}",
                url: url('/guru/attendance?date=' . Carbon::parse($request->session_date)->format('Y-m-d')),
                type: 'jurnal',
                icon: 'fa-circle-xmark'
            ));
        }

        return back()->with('success', 'Permohonan buka kunci jurnal telah ditolak dan guru telah diberitahu.');
    }
}
