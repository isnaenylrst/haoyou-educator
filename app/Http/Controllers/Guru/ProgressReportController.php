<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AchievementLevel;
use App\Models\ClassLearningMaterial;
use App\Models\ClassModel;
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
            ->where('c.teacher_response', 'Accepted')
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
     * Logika hitung kelas pengganti:
     * Jika siswa tidak masuk di pertemuan tertentu lalu menggantinya (misalnya absen di pertemuan 5 dan masuk double di pertemuan 6),
     * hitung sebagai 1 kali kelas pengganti secara otomatis dari data absensi.
     */
    private function replacementClassCount(int $classId, int $studentId): int
    {
        $absences = DB::table('attendances as a')
            ->join('teaching_journals as j', 'j.id', '=', 'a.teaching_journal_id')
            ->where('j.class_id', $classId)
            ->where('a.student_id', $studentId)
            ->whereIn('a.status', ['Absent', 'Sick', 'Permission'])
            ->count();

        // Hari di mana siswa masuk double di kelas tersebut
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
        $classes = DB::table('classes')->where('teacher_id', $teacher->id)->where('teacher_response', 'Accepted')->orderBy('class_name')->get();
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

        // Template Learning Materials per kelas (contoh Kelas Feixiang 1A)
        $classMaterials = ClassLearningMaterial::where('class_id', $e->class_id)->orderBy('sort_order')->get();
        if ($classMaterials->isEmpty()) {
            // fallback ke kompetensi level jika kelas belum diset spesifik
            $classMaterials = ReportCompetency::forLevel($e->class_level_id)->get()->map(function ($c) {
                return (object) ['id' => $c->id, 'material_name' => $c->name];
            });
        }

        // Pilihan Achievement Level baku sesuai ketentuan poin 6: Sufficient, Proficient, Excellent
        $achievementChoices = ['Sufficient', 'Proficient', 'Excellent'];
        $levelDescriptions = AchievementLevel::pluck('description', 'level')->all();

        // Nilai yang tersimpan sebelumnya
        $savedMaterials = collect($report->content ?? [])->keyBy('material');
        $savedAssessments = $report->assessment_data ?? [
            ['assessment' => 'Speaking & Pronunciation', 'score' => '85', 'grade' => 'A', 'note' => 'Percakapan lancar'],
            ['assessment' => 'Reading Comprehension',   'score' => '88', 'grade' => 'A', 'note' => 'Memahami teks dengan baik'],
            ['assessment' => 'Writing (Pinyin / Hanzi)', 'score' => '80', 'grade' => 'B', 'note' => 'Latihan urutan goresan'],
        ];

        $deadline = ProgressReportService::deadline($e->class_id, $report);
        $isLate = $deadline && now()->gt($deadline);

        return view('guru.progress-report-form', compact(
            'e', 'report', 'present', 'total', 'replacementCount', 'classMaterials',
            'achievementChoices', 'levelDescriptions', 'savedMaterials', 'savedAssessments', 'deadline', 'isLate'
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

        $data = $request->validate([
            'report_type'             => 'required|string|max:100',
            'report_period'           => 'required|string|max:100',
            'materials'               => 'required|array|min:1',
            'materials.*.material'    => 'required|string|max:255',
            'materials.*.level'       => ['required', Rule::in(['Sufficient', 'Proficient', 'Excellent', 'Good', 'Needs Improvement'])],
            'materials.*.description' => 'required|string|max:1000',
            'assessments'             => 'nullable|array',
            'assessments.*.assessment'=> 'required|string|max:255',
            'assessments.*.score'     => 'required|numeric|min:0|max:100',
            'assessments.*.grade'     => 'required|string|max:10',
            'assessments.*.note'      => 'nullable|string|max:255',
            'suggestion_box'          => 'nullable|string|max:2000',
            'action'                  => 'required|in:draft,submit',
        ]);

        [$present, $total] = $this->attendanceStats($e->class_id, $e->student_id);
        $replacementCount = $this->replacementClassCount($e->class_id, $e->student_id);

        $report->fill([
            'student_id'              => $e->student_id,
            'teacher_id'              => $teacher->id,
            'enrollment_id'           => $e->id,
            'report_type'             => $data['report_type'],
            'report_period'           => $data['report_period'],
            'content'                 => $data['materials'],
            'assessment_data'         => $data['assessments'] ?? [],
            'suggestion_box'          => $data['suggestion_box'] ?? null,
            'attendance_present'      => $present,
            'attendance_total'        => $total,
            'replacement_class_count' => $replacementCount,
            'uploaded_by'             => Auth::id(),
            'status'                  => 'Draft',
        ])->save();

        // generate PDF
        $pdf = Pdf::loadView('guru.progress-report-pdf', [
            'report' => $report, 'teacher' => $teacher, 'e' => $e,
        ])->setPaper('a4');

        $path = "progress-reports/report-{$report->id}.pdf";
        Storage::disk('public')->put($path, $pdf->output());
        $report->file_path = $path;

        if ($data['action'] === 'submit') {
            $report->status       = 'Submitted';
            $report->submitted_at = now();
            $report->returned_at  = null;
            $report->review_note  = null;

            // Kirim notifikasi database ke Kurikulum
            $curriculums = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Curriculum'))->get();
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

        $report->save();

        return redirect()->route('teacher.progress-report', ['class_id' => $e->class_id])
            ->with('success', $data['action'] === 'submit'
                ? "Progress Report untuk {$e->student_name} berhasil diajukan ke Kurikulum untuk di-ACC."
                : "Draft Progress Report untuk {$e->student_name} berhasil disimpan.");
    }

    public function download(ProgressReport $report)
    {
        abort_unless($report->file_path, 404);
        $path = storage_path('app/public/' . $report->file_path);
        abort_unless(file_exists($path), 404, 'File PDF tidak ditemukan.');

        return response()->download($path, 'Progress_Report_' . str_replace(' ', '_', $report->student?->name ?? 'Siswa') . '.pdf');
    }
}
