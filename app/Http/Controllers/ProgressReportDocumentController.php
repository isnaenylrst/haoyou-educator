<?php

namespace App\Http\Controllers;

use App\Models\ProgressReport;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\ProgressReportDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dokumen Progress Report yang dipakai semua role.
 * Hak akses selalu dicek di sini (bukan hanya di view / menu).
 */
class ProgressReportDocumentController extends Controller
{
    private function authorizeView(ProgressReport $report): void
    {
        abort_unless(ProgressReportDocument::canView(Auth::user(), $report), 403, 'Anda tidak berhak membuka dokumen ini.');
    }

    private function fileName(ProgressReport $report): string
    {
        return 'Progress_Report_' . Str::slug($report->student?->name ?? 'siswa', '_') . '.pdf';
    }

    /** Pratinjau dokumen (HTML) - dipakai Owner sebelum TTD, Kurikulum sebelum ACC, dll. */
    public function show(ProgressReport $report)
    {
        $this->authorizeView($report);

        return view('progress-report.show', [
            'report'  => $report,
            'doc'     => ProgressReportDocument::data($report),
            'canEdit' => ProgressReportDocument::canEdit(Auth::user(), $report),
        ]);
    }

    /** PDF; dibuat ulang bila belum ada agar selalu tersedia. */
    public function pdf(ProgressReport $report)
    {
        $this->authorizeView($report);

        $path = ProgressReportDocument::absolutePath($report);
        if (!$path) {
            ProgressReportDocument::store($report);
            $path = ProgressReportDocument::absolutePath($report);
        }
        abort_unless($path, 404, 'File PDF tidak dapat dibuat.');

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $this->fileName($report) . '"',
        ]);
    }

    private function layoutFor(User $user): string
    {
        return match ($user->level?->nama_level) {
            'Owner'      => 'layouts.owner',
            'Curriculum' => 'layouts.kurikulum',
            default      => 'layouts.guru',
        };
    }

    /** Editor untuk Kurikulum/Owner pada report yang sudah diajukan (sebelum ACC). */
    public function edit(ProgressReport $report)
    {
        abort_unless(ProgressReportDocument::canEdit(Auth::user(), $report), 403, 'Dokumen ini tidak bisa diedit.');
        $d = ProgressReportDocument::data($report);

        return view('progress-report.editor', [
            'layout'     => $this->layoutFor(Auth::user()),
            'formAction' => route('progress-report.document.update', $report),
            'method'     => 'PUT',
            'backUrl'    => url('/kurikulum/review-pengajuan?tab=report'),
            'header'     => ['title' => $d['title'], 'teacher' => $d['teacher'], 'student' => $d['student'], 'class' => $d['class'], 'present' => $d['present'], 'teacher_sign' => $d['teacher_sign_name']],
            'rows'       => $d['rows'],
            'assessments' => $d['assessments'] ?: [['assessment' => '', 'score' => '', 'grade' => '', 'note' => '']],
            'overall'    => $report->overall_achievement_level ?: 'Proficient',
            'suggestion' => $report->suggestion_box,
            'period'     => $report->report_period,
            'legend'     => $d['legend'],
            'achievementHtml'        => $d['achievement_html'],
            'defaultAchievementHtml' => ProgressReportDocument::defaultAchievementHtml(),
            'report'     => $report,
            'canDraft'   => true,
            'canSubmit'  => false,
            'draftLabel' => 'Simpan Perubahan',
            'notice'     => 'Anda mengedit teks report guru sebelum di-ACC. Perubahan langsung masuk ke PDF.',
        ]);
    }

    public function update(Request $request, ProgressReport $report)
    {
        abort_unless(ProgressReportDocument::canEdit(Auth::user(), $report), 403, 'Dokumen ini tidak bisa diedit.');

        $request->merge(['materials' => ProgressReportDocument::cleanRows((array) $request->input('materials', []))]);
        $data = $request->validate(ProgressReportDocument::rules(false), ProgressReportDocument::messages());

        DB::transaction(function () use ($report, $data) {
            ProgressReportDocument::fillFrom($report, $data);
            $report->save();
            ProgressReportDocument::store($report);

            if ($report->teacher?->user) {
                $report->teacher->user->notify(new AppNotification(
                    title: 'Progress Report Diedit Kurikulum',
                    message: 'Teks progress report ' . ($report->student?->name ?? '') . ' disesuaikan sebelum ACC.',
                    url: url('/guru/progress-report'),
                    type: 'progress_report',
                    icon: 'fa-pen-to-square'
                ));
            }
        });

        return redirect()->route('progress-report.document', $report)->with('success', 'Perubahan dokumen disimpan dan PDF diperbarui.');
    }
}