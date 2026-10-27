<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AchievementLevel;
use App\Models\ProgressReport;
use App\Models\ReportCompetency;
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
            ->where('ce.id', $id)->where('c.teacher_id', $this->teacher()->id)->where('c.teacher_response', 'Accepted')
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

    private function draftFor(int $enrollmentId): ?ProgressReport
    {
        return ProgressReport::where('enrollment_id', $enrollmentId)
            ->where('status', 'Draft')->latest('id')->first();
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

                    // Batas H+3 hanya relevan selama report belum dikirim
                    $row->deadline = ($row->report && $row->report->status === 'Submitted')
                        ? null
                        : ProgressReportService::deadline($classId, $row->report);
                    $row->is_late = $row->deadline && now()->gt($row->deadline);

                    // Sertifikat otomatis
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

        $competencies = ReportCompetency::forLevel($e->class_level_id)->get();
        $levels = AchievementLevel::orderBy('sort_order')->get();

        // Nilai yang sudah pernah diisi, dicocokkan berdasarkan competency_id
        $saved = collect($report->content ?? [])
            ->filter(fn ($r) => isset($r['competency_id']))
            ->keyBy('competency_id');

        $deadline = ProgressReportService::deadline($e->class_id, $report);
        $isLate = $deadline && now()->gt($deadline);

        return view('guru.progress-report-form', compact(
            'e', 'report', 'present', 'total', 'competencies', 'levels', 'saved', 'deadline', 'isLate'
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

        $competencies = ReportCompetency::forLevel($e->class_level_id)->get();
        if ($competencies->isEmpty()) {
            return back()->with('error', 'Kurikulum belum menetapkan kompetensi untuk level kelas ini.');
        }

        $descriptions = AchievementLevel::descriptionMap();

        $data = $request->validate([
            'report_type'          => 'required|string|max:100',
            'report_period'        => 'required|string|max:100',
            'rows'                 => 'required|array|min:1',
            'rows.*.competency_id' => 'required|integer',
            'rows.*.level'         => ['required', Rule::in(array_keys($descriptions))],
            'rows.*.score'         => 'required|numeric|min:0|max:100',
            'action'               => 'required|in:draft,submit',
        ]);

        // Susun ulang baris sesuai kompetensi dari Kurikulum.
        // Deskripsi diambil dari tabel baku, bukan dari input guru.
        $rows = [];
        foreach ($competencies as $comp) {
            $input = collect($data['rows'])->first(fn ($r) => (int) $r['competency_id'] === (int) $comp->id);

            if (!$input) {
                return back()->withInput()->with('error', "Nilai untuk kompetensi \"{$comp->name}\" belum diisi.");
            }

            $rows[] = [
                'competency_id' => $comp->id,
                'competency'    => $comp->name,
                'level'         => $input['level'],
                'score'         => (float) $input['score'],
                'description'   => $descriptions[$input['level']],
            ];
        }

        [$present, $total] = $this->attendanceStats($e->class_id, $e->student_id);

        $report->fill([
            'student_id'         => $e->student_id,
            'teacher_id'         => $teacher->id,
            'enrollment_id'      => $e->id,
            'report_type'        => $data['report_type'],
            'report_period'      => $data['report_period'],
            'content'            => $rows,
            'attendance_present' => $present,
            'attendance_total'   => $total,
            'uploaded_by'        => Auth::id(),
            'status'             => 'Draft',
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
            // kirim ulang setelah dikembalikan: bersihkan jejak pengembalian
            $report->returned_at  = null;
            $report->review_note  = null;
            $report->reviewed_by  = null;
            $report->reviewed_at  = null;
        }

        $report->save();

        return redirect()->route('teacher.progress-report', ['class_id' => $e->class_id])
            ->with('success', $data['action'] === 'submit'
                ? 'Progress report dikirim ke Kurikulum. Siswa baru bisa melihatnya setelah Kurikulum menyetujui.'
                : 'Draft progress report disimpan.');
    }

    public function download(ProgressReport $report)
    {
        abort_if((int) $report->teacher_id !== (int) $this->teacher()->id || !$report->file_path, 403);

        $path = storage_path('app/public/' . $report->file_path);
        abort_unless(file_exists($path), 404, 'File PDF tidak ditemukan.');

        $name = 'Progress_Report_' . str_replace(' ', '_', optional($report->student)->name) . '.pdf';

        return response()->download($path, $name);
    }

    public function submitAll(Request $request)
    {
        $drafts = ProgressReport::with('enrollment')
            ->where('teacher_id', $this->teacher()->id)
            ->where('status', 'Draft')
            ->whereNotNull('file_path')
            ->whereNull('returned_at')   // yang dikembalikan harus diperbaiki dulu lewat form
            ->get();

        $sent = 0;
        $late = 0;

        foreach ($drafts as $draft) {
            $classId = optional($draft->enrollment)->class_id;

            if ($classId && ProgressReportService::isPastDeadline((int) $classId, $draft)) {
                $late++;
                continue;
            }

            $draft->update([
                'status'       => 'Submitted',
                'submitted_at' => now(),
            ]);
            $sent++;
        }

        $message = "$sent progress report dikirim ke Kurikulum.";
        if ($late > 0) {
            $message .= " $late report tidak dikirim karena melewati batas H+" . ProgressReportService::GRACE_DAYS . '.';
        }

        return back()->with($sent > 0 || $late === 0 ? 'success' : 'error', $message);
    }
}