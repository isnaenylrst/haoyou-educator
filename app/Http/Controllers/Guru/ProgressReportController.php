<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Material;
use App\Models\ProgressReport;
use App\Models\ProgressReportTemplate;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\ProgressReportDocument;
use App\Services\ProgressReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProgressReportController extends Controller
{
    private function teacher() { return Auth::user()->teacher; }

    /** Ambil enrollment milik kelas guru ini, atau 404 */
    private function myEnrollment(int $id)
    {
        $e = DB::table('class_enrollments as ce')
            ->join('classes as c', 'c.id', '=', 'ce.class_id')
            ->join('students as s', 's.id', '=', 'ce.student_id')
            ->where('ce.id', $id)
            ->where('c.teacher_id', $this->teacher()->id)
            ->select('ce.*', 'c.class_name', 'c.level_id as class_level_id', 's.name as student_name')
            ->first();
        abort_if(!$e, 404);
        return $e;
    }

    private function attendanceStats(int $classId, int $studentId): array
    {
        $total = DB::table('teaching_journals')
            ->where('class_id', $classId)->where('class_status', 'Conducted')->count();

        $present = DB::table('attendances as a')
            ->join('teaching_journals as j', 'j.id', '=', 'a.teaching_journal_id')
            ->where('j.class_id', $classId)->where('j.class_status', 'Conducted')
            ->where('a.student_id', $studentId)->where('a.status', 'Present')->count();

        return [$present, $total];
    }

    /**
     * Hitung kelas pengganti secara otomatis dari data absensi.
     */
    private function replacementClassCount(int $classId, int $studentId): int
    {
        $absences = DB::table('attendances as a')
            ->join('teaching_journals as j', 'j.id', '=', 'a.teaching_journal_id')
            ->where('j.class_id', $classId)
            ->where('a.student_id', $studentId)
            ->whereIn('a.status', ['Absent', 'Sick', 'Permission'])
            ->count();

        $doubleAttendances = DB::table('attendances as a')
            ->join('teaching_journals as j', 'j.id', '=', 'a.teaching_journal_id')
            ->where('j.class_id', $classId)
            ->where('a.student_id', $studentId)
            ->where('a.status', 'Present')
            ->select('j.session_date', DB::raw('COUNT(*) as total_sesi'))
            ->groupBy('j.session_date')
            ->having('total_sesi', '>', 1)
            ->get();

        $extraSessions = $doubleAttendances->sum(fn ($d) => $d->total_sesi - 1);

        return min($absences, $extraSessions);
    }

    private function draftFor(int $enrollmentId): ?ProgressReport
    {
        return ProgressReport::where('enrollment_id', $enrollmentId)
            ->whereIn('status', ['Draft', 'Submitted'])->latest('id')->first();
    }

    private function deadlineMessage(int $classId, ?ProgressReport $report): string
    {
        $deadline = ProgressReportService::deadline($classId, $report);

        return 'Batas pengisian progress report (H+' . ProgressReportService::GRACE_DAYS
            . ' setelah pertemuan terakhir) sudah lewat pada '
            . optional($deadline)->translatedFormat('d M Y')
            . '. Hubungi Kurikulum untuk membuka kembali.';
    }

    public function index(Request $request)
    {
        $teacher = $this->teacher();
        $classes = DB::table('classes')->where('teacher_id', $teacher->id)->orderBy('class_name')->get();
        $classId = (int) ($request->class_id ?: optional($classes->first())->id);

        $students = collect();
        if ($classId && $classes->contains('id', $classId)) {
            $students = DB::table('class_enrollments as ce')
                ->join('students as s', 's.id', '=', 'ce.student_id')
                ->where('ce.class_id', $classId)->where('ce.status', '!=', 'Cancelled')
                ->select('ce.id as enrollment_id', 'ce.status as enrollment_status', 's.id as student_id', 's.name')
                ->orderBy('s.name')->get()
                ->map(function ($row) use ($classId) {
                    $row->report = ProgressReport::where('enrollment_id', $row->enrollment_id)->latest('id')->first();
                    [$row->present, $row->total] = $this->attendanceStats($classId, $row->student_id);
                    $row->replacement_count = $this->replacementClassCount($classId, $row->student_id);

                    // Batas H+3 hanya relevan selama report belum dikirim
                    $row->deadline = ($row->report && in_array($row->report->status, ['Submitted', 'Published']))
                        ? null
                        : ProgressReportService::deadline($classId, $row->report);
                    $row->is_late = $row->deadline && now()->gt($row->deadline);

                    $row->certificate_ready = ProgressReportService::certificateEligible((object) [
                        'id'       => $row->enrollment_id,
                        'status'   => $row->enrollment_status,
                        'class_id' => $classId,
                    ]);

                    return $row;
                });
        }

        $history = ProgressReport::where('teacher_id', $teacher->id)
            ->whereNotNull('file_path')->with('student')->latest('id')->take(15)->get();

        $template = $classId ? ProgressReportTemplate::forClass($classId) : null;
        $templateMissing = $classId && $classes->contains('id', $classId) && !$template && config('progress_report.require_template');
        $templateName = $template?->name;
        $templateMessage = $templateMissing ? $this->templateMissingMessage($classId) : null;

        return view('guru.progress-report', compact('classes', 'classId', 'students', 'history', 'templateMissing', 'templateName', 'templateMessage'));
    }

    /** Baris awal editor (hanya bila template tidak diwajibkan): report tersimpan, jurnal mengajar, lalu satu baris kosong. */
    private function initialRows(object $e, ?ProgressReport $report): array
    {
        if ($report && !empty($report->content)) {
            return collect($report->content)->map(fn ($r) => [
                'meeting' => $r['meeting'] ?? '', 'material' => $r['material'] ?? '',
                'level' => $r['level'] ?? '', 'description' => $r['description'] ?? '',
            ])->values()->all();
        }

        $journals = DB::table('teaching_journals as j')
            ->leftJoin('materials as m', 'm.id', '=', 'j.material_id')
            ->where('j.class_id', $e->class_id)->where('j.class_status', 'Conducted')
            ->orderBy('j.session_date')->orderBy('j.id')
            ->get(['j.meeting_number', 'j.material_taught', 'j.learning_activities', 'm.title as material_title']);

        if ($journals->isNotEmpty()) {
            return $journals->values()->map(fn ($j, $i) => [
                'meeting'     => $j->meeting_number ?: ($i + 1),
                'material'    => trim((string) ($j->material_taught ?: ($j->material_title ?: $j->learning_activities))),
                'level'       => '',
                'description' => '',
            ])->all();
        }

        

        return [['meeting' => 1, 'material' => '', 'level' => '', 'description' => '']];
    }

    /** Baris editor dari template Kurikulum; level & description diambil dari isian guru yang sudah tersimpan. */
    private function templateRows(ProgressReportTemplate $template, ?ProgressReport $report): array
    {
        $saved = collect($report?->content ?? [])->keyBy(fn ($r) => (string) (int) ($r['meeting'] ?? 0));

        return $template->meetings->map(function ($m) use ($saved) {
            $old = $saved->get((string) $m->meeting_number, []);

            return [
                'meeting'     => $m->meeting_number,
                'material'    => $m->materials,   // dikunci: dari Kurikulum
                'level'       => $old['level'] ?? '',
                'description' => $old['description'] ?? '',
            ];
        })->values()->all();
    }

    private function templateMissingMessage(int $classId): string
    {
        return 'Kurikulum belum membuat template Learning Materials untuk program '
            . ProgressReportTemplate::programLabelForClass($classId)
            . '. Hubungi Kurikulum agar membuatnya di menu Template Progress Report.';
    }

    public function form(int $enrollment)
    {
        $e = $this->myEnrollment($enrollment);
        if (config('progress_report.require_template') && !ProgressReportTemplate::forClass($e->class_id)) {
            return redirect()->route('teacher.progress-report', ['class_id' => $e->class_id])
                ->with('error', $this->templateMissingMessage($e->class_id));
        }
        $teacher = $this->teacher();
        $report = $this->draftFor($e->id);
        [$present, $total] = $this->attendanceStats($e->class_id, $e->student_id);

        if ($report && $report->status === 'Submitted') {
            return redirect()->route('progress-report.document', $report)
                ->with('success', 'Report sudah diajukan dan menunggu review Kurikulum.');
        }

        $h = (array) ($report->header_data ?? []);
        $header = [
            'title'        => $h['title'] ?? config('progress_report.default_title'),
            'teacher'      => $h['teacher'] ?? $teacher->name,
            'student'      => $h['student'] ?? $e->student_name,
            'class'        => $h['class'] ?? $e->class_name,
            'present'      => $h['present'] ?? ($total ? "{$present}/{$total}" : ''),
            'teacher_sign' => $h['teacher_sign'] ?? (Auth::user()->name ?: $teacher->name),
        ];

        $assessments = !empty($report->assessment_data) ? $report->assessment_data : [
            ['assessment' => '', 'score' => '', 'grade' => '', 'note' => ''],
        ];

        $deadline = ProgressReportService::deadline($e->class_id, $report);
        $isLate = $deadline && now()->gt($deadline);

        $template = ProgressReportTemplate::forClass($e->class_id);
        $notice = $isLate ? $this->deadlineMessage($e->class_id, $report) : ($report?->review_note ? 'Dikembalikan Kurikulum: ' . $report->review_note : null);
        if (!$template && !$notice) {
            $notice = 'Kurikulum belum membuat template Learning Materials untuk program kelas ini, jadi kolom Learning Materials masih bisa Anda isi sendiri.';
        }

        return view('progress-report.editor', [
            'lockMaterials' => (bool) $template,
            'templateName'  => $template?->name,
            'layout'     => 'layouts.guru',
            'formAction' => route('teacher.progress-report.store', $e->id),
            'method'     => 'POST',
            'backUrl'    => route('teacher.progress-report', ['class_id' => $e->class_id]),
            'header'     => $header,
            'rows'       => $template ? $this->templateRows($template, $report) : $this->initialRows($e, $report),
            'assessments' => $assessments,
            'overall'    => $report->overall_achievement_level ?? 'Proficient',
            'suggestion' => $report->suggestion_box ?? '',
            'period'     => $report->report_period ?? '',
            'legend'     => ProgressReportDocument::legend(),
            'achievementHtml'        => array_key_exists('achievement_page', $h) ? (string) $h['achievement_page'] : ProgressReportDocument::defaultAchievementHtml(),
            'defaultAchievementHtml' => ProgressReportDocument::defaultAchievementHtml(),
            'report'     => $report,
            'canDraft'   => !$isLate,
            'canSubmit'  => !$isLate,
            'notice'     => $notice,
            'deleteUrl'  => ($report && $report->exists && $report->status === 'Draft') ? route('teacher.progress-report.destroy', $report) : null,
        ]);
    }

    public function store(Request $request, int $enrollment)
    {
        $e = $this->myEnrollment($enrollment);
        if (config('progress_report.require_template') && !ProgressReportTemplate::forClass($e->class_id)) {
            return back()->with('error', $this->templateMissingMessage($e->class_id));
        }
        $teacher = $this->teacher();

        $report = $this->draftFor($e->id) ?? new ProgressReport();

        if ($report->exists && $report->status === 'Submitted') {
            return back()->with('error', 'Report sudah diajukan; tunggu review Kurikulum.');
        }

        if (ProgressReportService::isPastDeadline($e->class_id, $report->exists ? $report : null)) {
            return back()->withInput()->with('error', $this->deadlineMessage($e->class_id, $report->exists ? $report : null));
        }

        // bersihkan HTML dari editor (aman + "kosong" dikenali) sebelum disaring/divalidasi
        $request->merge(['materials' => ProgressReportDocument::cleanRows((array) $request->input('materials', []))]);

        // Learning Materials dikunci dari template Kurikulum: dipaksa dari server, abaikan teks kiriman form.
        $template = ProgressReportTemplate::forClass($e->class_id);
        if ($template) {
            $byNumber = $template->meetings->keyBy(fn ($m) => (string) $m->meeting_number);
            $rows = collect($request->input('materials', []))
                ->filter(fn ($m) => is_array($m)
                    && $byNumber->has((string) (int) ($m['meeting'] ?? 0))
                    && (filled($m['level'] ?? null) || filled($m['description'] ?? null)))
                ->map(function ($m) use ($byNumber) {
                    $n = (string) (int) $m['meeting'];
                    $m['meeting']  = $n;
                    $m['material'] = $byNumber[$n]->materials;

                    return $m;
                })
                ->unique('meeting')
                ->sortBy(fn ($m) => (int) $m['meeting'])
                ->values()->all();
            $request->merge(['materials' => $rows]);
        }

        $action = $request->input('action', 'draft') === 'submit' ? 'submit' : 'draft';
        $data = $request->validate(ProgressReportDocument::rules($action === 'submit'), ProgressReportDocument::messages());

        [$present, $total] = $this->attendanceStats($e->class_id, $e->student_id);
        $replacementCount = $this->replacementClassCount($e->class_id, $e->student_id);

        DB::transaction(function () use (&$report, $e, $teacher, $data, $present, $total, $replacementCount, $action) {
            ProgressReportDocument::fillFrom($report, $data);
            $report->fill([
                'student_id'              => $e->student_id,
                'teacher_id'              => $teacher->id,
                'enrollment_id'           => $e->id,
                'attendance_present'      => $present,
                'attendance_total'        => $total,
                'replacement_class_count' => $replacementCount,
                'uploaded_by'             => Auth::id(),
                'status'                  => 'Draft',
            ]);
            $report->save();

            if ($action === 'submit') {
                $report->update([
                    'status' => 'Submitted', 'submitted_at' => now(), 'returned_at' => null, 'review_note' => null,
                ]);

                $curriculums = User::whereHas('level', fn ($q) => $q->whereIn('nama_level', ['Curriculum', 'Kurikulum']))->get();
                foreach ($curriculums as $curr) {
                    $curr->notify(new AppNotification(
                        title: 'Progress Report Diajukan',
                        message: "Guru {$teacher->name} telah mengajukan Progress Report untuk {$e->student_name} ({$e->class_name}).",
                        url: url('/kurikulum/review-pengajuan?tab=report'),
                        type: 'progress_report',
                        icon: 'fa-chart-column'
                    ));
                }
            }

            ProgressReportDocument::store($report);
        });

        if ($action === 'draft') {
            return redirect()->route('teacher.progress-report.form', $e->id)
                ->with('success', "Draft Progress Report untuk {$e->student_name} disimpan. Anda bisa melihat PDF-nya lewat tombol Pratinjau/PDF.");
        }

        return redirect()->route('teacher.progress-report', ['class_id' => $e->class_id])
            ->with('success', "Progress Report untuk {$e->student_name} berhasil diajukan ke Kurikulum untuk di-ACC.");
    }

    public function submitAll(Request $request)
    {
        $classId = (int) $request->class_id;
        $teacher = $this->teacher();

        $drafts = ProgressReport::where('teacher_id', $teacher->id)
            ->where('status', 'Draft')
            ->whereHas('enrollment', fn ($q) => $q->where('class_id', $classId))
            ->get();

        if ($drafts->isEmpty()) {
            return back()->with('error', 'Tidak ada draft progress report yang siap diajukan untuk kelas ini.');
        }

        foreach ($drafts as $report) {
            $report->update([
                'status'       => 'Submitted',
                'submitted_at' => now(),
                'returned_at'  => null,
                'review_note'  => null,
            ]);
        }

        $curriculums = User::whereHas('level', fn ($q) => $q->whereIn('nama_level', ['Curriculum', 'Kurikulum']))->get();
        foreach ($curriculums as $curr) {
            $curr->notify(new AppNotification(
                title: "Progress Report Diajukan Sekaligus",
                message: "Guru {$teacher->name} mengajukan {$drafts->count()} Progress Report sekaligus untuk kelas tersebut.",
                url: url('/kurikulum/review-pengajuan?tab=report'),
                type: 'progress_report',
                icon: 'fa-chart-column'
            ));
        }

        return redirect()->route('teacher.progress-report', ['class_id' => $classId])
            ->with('success', "{$drafts->count()} Progress Report berhasil diajukan ke Kurikulum.");
    }

    /** Guru boleh menghapus draft miliknya (report yang sudah diajukan tidak bisa dihapus). */
    public function destroy(ProgressReport $report)
    {
        abort_unless((int) $report->teacher_id === (int) $this->teacher()->id, 403, 'Bukan report Anda.');
        abort_unless($report->status === 'Draft', 403, 'Hanya draft yang bisa dihapus.');

        $classId = DB::table('class_enrollments')->where('id', $report->enrollment_id)->value('class_id');
        $name = $report->student?->name ?? 'siswa';

        if ($report->file_path) {
            foreach ([ProgressReportDocument::DISK, 'public'] as $disk) {
                \Illuminate\Support\Facades\Storage::disk($disk)->delete($report->file_path);
            }
        }
        $report->delete();

        return redirect()->route('teacher.progress-report', $classId ? ['class_id' => $classId] : [])
            ->with('success', "Draft Progress Report {$name} dihapus.");
    }

    public function download(ProgressReport $report)
    {
        abort_unless(ProgressReportDocument::canView(Auth::user(), $report), 403, 'Anda tidak berhak mengunduh report ini.');

        return redirect()->route('progress-report.document.pdf', $report);
    }
}