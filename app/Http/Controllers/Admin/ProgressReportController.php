<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgressReport;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Progress report dibuat oleh laoshi. Admin hanya melihat & mengunduh
 * laporan yang sudah diterbitkan (status = Submitted).
 */
class ProgressReportController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $teacherId = $request->integer('teacher_id') ?: null;
        $type = $request->query('report_type');

        $stats = [
            'terbit' => ProgressReport::where('status', 'Submitted')->count(),
            'bulanan' => ProgressReport::where('status', 'Submitted')->where('report_type', 'Bulanan')->count(),
            'akhir' => ProgressReport::where('status', 'Submitted')->where('report_type', 'Akhir Paket')->count(),
            'draft' => ProgressReport::where('status', 'Draft')->count(),
        ];

        $reports = ProgressReport::query()
            ->with([
                'student',
                'teacher',
                'enrollment.class.programPackage.program',
                'enrollment.programPackage.program',
                'enrollment.privatePackage',
            ])
            ->where('status', 'Submitted')
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('report_period', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            }))
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($type, fn ($q) => $q->where('report_type', $type))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $teachers = Teacher::whereHas('progressReports', fn ($q) => $q->where('status', 'Submitted'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $types = ProgressReport::where('status', 'Submitted')
            ->distinct()
            ->orderBy('report_type')
            ->pluck('report_type');

        return view('admin.progress-report', compact('reports', 'stats', 'teachers', 'types'));
    }

    /** Buka PDF di tab baru */
    public function lihat(ProgressReport $report)
    {
        $disk = $this->resolveFile($report);

        if (! $disk) {
            return $this->missingFile();
        }

        return Storage::disk('public')->response($report->file_path, $this->fileName($report));
    }

    /** Unduh PDF */
    public function unduh(ProgressReport $report)
    {
        if (! $this->resolveFile($report)) {
            return $this->missingFile();
        }

        return Storage::disk('public')->download($report->file_path, $this->fileName($report));
    }

    private function resolveFile(ProgressReport $report): bool
    {
        return $report->status === 'Submitted'
            && $report->file_path
            && Storage::disk('public')->exists($report->file_path);
    }

    private function missingFile()
    {
        return redirect()->route('admin.progress-report')
            ->with('error', 'File laporan tidak ditemukan atau laporan belum diterbitkan laoshi.');
    }

    private function fileName(ProgressReport $report): string
    {
        $ext = pathinfo($report->file_path, PATHINFO_EXTENSION) ?: 'pdf';

        return Str::slug($report->student?->name ?? 'siswa')
            . '-progress-report-' . Str::slug($report->report_period)
            . '.' . $ext;
    }
}
