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
    private const TABS     = ['material', 'jurnal', 'report', 'cuti', 'sertifikat'];

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

        $lateEnrollments = $this->lateEnrollments();

        $totals = [
            'material'   => $materials->total(),
            'jurnal'     => $journals->total(),
            'report'     => $reports->total() + $lateEnrollments->count(),
            'cuti'       => $leaves->total(),
            'sertifikat' => $certificates->total(),
        ];

        $totalPending = $materials->total() + $journals->total() + $reports->total()
            + $leaves->total() + $certificates->total();

        // Tab aktif: yang diminta (?tab=), atau tab pertama yang punya antrian
        $requested = $request->query('tab');
        $tab = in_array($requested, self::TABS, true)
            ? $requested
            : (collect($totals)->filter(fn ($n) => $n > 0)->keys()->first() ?? 'material');

        return view('kurikulum.review-pengajuan', compact(
            'todaySchedules', 'totalSessionToday', 'filledToday', 'unfilledToday', 'teacherNamesToday',
            'materials', 'journals', 'reports', 'leaves', 'certificates', 'lateEnrollments',
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
            'cert'   => Certificate::findOrFail($id)->file_path,
            'cuti'   => TeacherLeave::findOrFail($id)->supporting_document,
            default  => abort(404),
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
            $progressReport->update([
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_note' => null,
                'returned_at' => null,
            ]);
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
}