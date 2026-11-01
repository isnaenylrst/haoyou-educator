<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AchievementLevel;
use App\Models\ClassLearningMaterial;
use App\Models\ClassModel;
use App\Models\Material;
use App\Models\ProgressReport;
use App\Models\ReportCompetency;
use App\Models\User;
use App\Notifications\AppNotification;
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

        return view('guru.progress-report', compact('classes', 'classId', 'students', 'history'));
    }

    public function form(int $enrollment)
    {
        $e = $this->myEnrollment($enrollment);
        $report = $this->draftFor($e->id);
        [$present, $total] = $this->attendanceStats($e->class_id, $e->student_id);
        $replacementCount = $this->replacementClassCount($e->class_id, $e->student_id);

        // 1. Template Learning Materials per kelas
        $classMaterials = ClassLearningMaterial::where('class_id', $e->class_id)->orderBy('sort_order')->get();

        // 2. Fallback ke ReportCompetency level
        if ($classMaterials->isEmpty() && $e->class_level_id) {
            $classMaterials = ReportCompetency::forLevel($e->class_level_id)->get()->map(function ($c) {
                return (object) ['id' => $c->id, 'material_name' => $c->name];
            });
        }

        // 3. Fallback ke isi report yang tersimpan sebelumnya
        if ($classMaterials->isEmpty() && $report && !empty($report->content)) {
            $classMaterials = collect($report->content)->map(function ($row, $idx) {
                return (object) ['id' => $idx + 1, 'material_name' => $row['material'] ?? "Materi " . ($idx + 1)];
            });
        }

        // 4. Fallback ke materi kurikulum yang ada di level tersebut
        if ($classMaterials->isEmpty() && $e->class_level_id) {
            $classMaterials = Material::where('level_id', $e->class_level_id)->orderBy('meeting_number')->take(6)->get()->map(function ($m) {
                return (object) ['id' => $m->id, 'material_name' => $m->title];
            });
        }

        // 5. Fallback ke kompetensi standar agar form tidak pernah kosong
        if ($classMaterials->isEmpty()) {
            $classMaterials = collect([
                (object) ['id' => 1, 'material_name' => 'Penguasaan Kosakata (Vocabulary)'],
                (object) ['id' => 2, 'material_name' => 'Tata Bahasa & Pola Kalimat (Grammar & Sentence Pattern)'],
                (object) ['id' => 3, 'material_name' => 'Pelafalan & Percakapan (Pronunciation & Speaking)'],
                (object) ['id' => 4, 'material_name' => 'Pemahaman Mendengar & Membaca (Listening & Reading Comprehension)'],
            ]);
        }

        // Pilihan Achievement Level baku
        $achievementChoices = ['Sufficient', 'Proficient', 'Excellent'];
        $levelDescriptions = AchievementLevel::pluck('description', 'level')->all();

        // Nilai yang tersimpan sebelumnya
        $savedMaterials = collect($report->content ?? [])->keyBy('material');
        $savedAssessments = !empty($report->assessment_data) ? $report->assessment_data : [
            ['assessment' => 'Speaking & Pronunciation', 'score' => '', 'grade' => '', 'note' => ''],
            ['assessment' => 'Reading Comprehension',   'score' => '', 'grade' => '', 'note' => ''],
            ['assessment' => 'Writing (Pinyin / Hanzi)', 'score' => '', 'grade' => '', 'note' => ''],
        ];

        $overallLevel = $report->overall_achievement_level ?? 'Proficient';

        $deadline = ProgressReportService::deadline($e->class_id, $report);
        $isLate = $deadline && now()->gt($deadline);

        return view('guru.progress-report-form', compact(
            'e', 'report', 'present', 'total', 'replacementCount', 'classMaterials',
            'achievementChoices', 'levelDescriptions', 'savedMaterials', 'savedAssessments', 'overallLevel', 'deadline', 'isLate'
        ));
    }

    public function store(Request $request, int $enrollment)
    {
        $e = $this->myEnrollment($enrollment);
        $teacher = $this->teacher();

        $report = $this->draftFor($e->id) ?? new ProgressReport();

        // Batas H+3
        if (ProgressReportService::isPastDeadline($e->class_id, $report->exists ? $report : null)) {
            return back()->withInput()->with('error', $this->deadlineMessage($e->class_id, $report->exists ? $report : null));
        }

        $action = $request->input('action', 'draft');

        // Auto-calculate grade pada input request sebelum validasi jika score terisi namun grade kosong
        $assessmentsInput = $request->input('assessments', []);
        if (is_array($assessmentsInput)) {
            foreach ($assessmentsInput as $k => $ass) {
                if ((!isset($ass['grade']) || trim((string) $ass['grade']) === '') && isset($ass['score']) && $ass['score'] !== '') {
                    $sc = (float) $ass['score'];
                    $assessmentsInput[$k]['grade'] = $sc >= 85 ? 'A' : ($sc >= 75 ? 'B' : ($sc >= 60 ? 'C' : 'D'));
                }
            }
            $request->merge(['assessments' => $assessmentsInput]);
        }

        if ($action === 'submit') {
            // Validasi lengkap saat diajukan ke Kurikulum
            $data = $request->validate([
                'report_type'               => 'required|string|max:100',
                'report_period'             => 'required|string|max:100',
                'materials'                 => 'required|array|min:1',
                'materials.*.material'      => 'required|string|max:255',
                'materials.*.level'         => ['required', Rule::in(['Sufficient', 'Proficient', 'Excellent', 'Good', 'Needs Improvement'])],
                'materials.*.description'   => 'required|string|max:1000',
                'assessments'               => 'required|array|min:1',
                'assessments.*.assessment'  => 'required|string|max:255',
                'assessments.*.score'       => 'required|numeric|min:0|max:100',
                'assessments.*.grade'       => 'required|string|max:10',
                'assessments.*.note'        => 'nullable|string|max:255',
                'overall_achievement_level' => ['required', Rule::in(['Sufficient', 'Proficient', 'Excellent'])],
                'suggestion_box'            => 'nullable|string|max:2000',
            ], [
                'materials.required'               => 'Daftar materi pembelajaran minimal harus ada 1 materi.',
                'materials.*.description.required' => 'Deskripsi capaian materi wajib diisi.',
                'assessments.*.score.required'     => 'Nilai score penilaian wajib diisi (0-100).',
                'assessments.*.grade.required'     => 'Grade penilaian wajib diisi.',
                'overall_achievement_level.required' => 'Overall Achievement Level wajib dipilih.',
            ]);
        } else {
            // Validasi fleksibel saat simpan Draft
            $data = $request->validate([
                'report_type'               => 'required|string|max:100',
                'report_period'             => 'required|string|max:100',
                'materials'                 => 'nullable|array',
                'materials.*.material'      => 'required|string|max:255',
                'materials.*.level'         => ['nullable', Rule::in(['Sufficient', 'Proficient', 'Excellent', 'Good', 'Needs Improvement'])],
                'materials.*.description'   => 'nullable|string|max:1000',
                'assessments'               => 'nullable|array',
                'assessments.*.assessment'  => 'nullable|string|max:255',
                'assessments.*.score'       => 'nullable|numeric|min:0|max:100',
                'assessments.*.grade'       => 'nullable|string|max:10',
                'assessments.*.note'        => 'nullable|string|max:255',
                'overall_achievement_level' => ['nullable', Rule::in(['Sufficient', 'Proficient', 'Excellent'])],
                'suggestion_box'            => 'nullable|string|max:2000',
            ]);
        }

        // Auto-calculate grade jika score diisi tapi grade kosong
        if (!empty($data['assessments'])) {
            foreach ($data['assessments'] as &$ass) {
                if (empty($ass['grade']) && isset($ass['score']) && $ass['score'] !== '') {
                    $sc = (float) $ass['score'];
                    $ass['grade'] = $sc >= 85 ? 'A' : ($sc >= 75 ? 'B' : ($sc >= 60 ? 'C' : 'D'));
                }
            }
            unset($ass);
        }

        // Filter materi yang memiliki nama
        $materials = collect($data['materials'] ?? [])
            ->filter(fn ($m) => !empty($m['material']))
            ->values()
            ->all();

        [$present, $total] = $this->attendanceStats($e->class_id, $e->student_id);
        $replacementCount = $this->replacementClassCount($e->class_id, $e->student_id);

        DB::transaction(function () use (&$report, $e, $teacher, $data, $materials, $present, $total, $replacementCount, $action) {
            $report->fill([
                'student_id'                => $e->student_id,
                'teacher_id'                => $teacher->id,
                'enrollment_id'             => $e->id,
                'report_type'               => $data['report_type'],
                'report_period'             => $data['report_period'],
                'content'                   => $materials,
                'assessment_data'           => $data['assessments'] ?? [],
                'overall_achievement_level' => $data['overall_achievement_level'] ?? 'Proficient',
                'suggestion_box'            => $data['suggestion_box'] ?? null,
                'attendance_present'        => $present,
                'attendance_total'          => $total,
                'replacement_class_count'   => $replacementCount,
                'uploaded_by'               => Auth::id(),
                'status'                    => 'Draft',
            ]);
            $report->save();

            // Generate PDF
            try {
                $pdf = Pdf::loadView('guru.progress-report-pdf', [
                    'report' => $report, 'teacher' => $teacher, 'e' => $e,
                ])->setPaper('a4');

                $path = "progress-reports/report-{$report->id}.pdf";
                Storage::disk('public')->put($path, $pdf->output());
                $report->file_path = $path;
                $report->save();
            } catch (\Throwable $pdfEx) {
                report($pdfEx);
            }

            if ($action === 'submit') {
                $report->update([
                    'status'       => 'Submitted',
                    'submitted_at' => now(),
                    'returned_at'  => null,
                    'review_note'  => null,
                ]);

                // Kirim notifikasi database ke Kurikulum
                $curriculums = User::whereHas('level', fn ($q) => $q->whereIn('nama_level', ['Curriculum', 'Kurikulum']))->get();
                foreach ($curriculums as $curr) {
                    $curr->notify(new AppNotification(
                        title: "Progress Report Diajukan",
                        message: "Guru {$teacher->name} telah mengajukan Progress Report untuk {$e->student_name} ({$e->class_name}).",
                        url: url('/kurikulum/review-pengajuan?tab=report'),
                        type: 'progress_report',
                        icon: 'fa-chart-column'
                    ));
                }
            }
        });

        return redirect()->route('teacher.progress-report', ['class_id' => $e->class_id])
            ->with('success', $action === 'submit'
                ? "Progress Report untuk {$e->student_name} berhasil diajukan ke Kurikulum untuk di-ACC."
                : "Draft Progress Report untuk {$e->student_name} berhasil disimpan.");
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

    public function download(ProgressReport $report)
    {
        abort_unless($report->file_path, 404);
        $path = storage_path('app/public/' . $report->file_path);
        abort_unless(file_exists($path), 404, 'File PDF tidak ditemukan.');

        return response()->download($path, 'Progress_Report_' . str_replace(' ', '_', $report->student?->name ?? 'Siswa') . '.pdf');
    }
}
