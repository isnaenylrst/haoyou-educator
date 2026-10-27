<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ClassEnrollment;
use App\Models\LearningVideo;
use App\Models\ProgressReport;
use Illuminate\Support\Facades\Auth;

class SiswaLearningController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Progress Report, Sertifikat, dan Video untuk siswa (data asli)
    |--------------------------------------------------------------------------
    | - Progress report : hanya yang sudah di-ACC Kurikulum
    | - Sertifikat      : hanya yang sudah di-ACC Kurikulum
    | - Video           : hanya yang dipublikasikan, sesuai level kelas aktif
    |--------------------------------------------------------------------------
    */

    private function student()
    {
        return Auth::user()->student;
    }

    /** Siswa yang sedang les = punya pendaftaran kelas berstatus Active. */
    private function isStudying($student): bool
    {
        return ClassEnrollment::where('student_id', $student->id)
            ->where('status', 'Active')
            ->exists();
    }

    /** Level dari kelas yang sedang diikuti siswa (status Active). */
    private function activeLevelIds($student): array
    {
        return ClassEnrollment::where('student_id', $student->id)
            ->where('status', 'Active')
            ->with('class:id,level_id')
            ->get()
            ->pluck('class.level_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    // ---------------------------------------------------------------- PROGRESS REPORT

    public function progresReport()
    {
        $student = $this->student();

        $laporan = $student
            ? ProgressReport::approved()
                ->where('student_id', $student->id)
                ->with('enrollment.class')
                ->orderByDesc('reviewed_at')
                ->get()
            : collect();

        return view('siswa.progress-report', [
            'title'    => 'Progress Report',
            'subtitle' => 'Laporan perkembangan belajar',
            'laporan'  => $laporan,
        ]);
    }

    public function downloadProgressReport(ProgressReport $report)
    {
        $student = $this->student();

        abort_if(
            !$student
                || (int) $report->student_id !== (int) $student->id
                || !$report->is_approved
                || !$report->file_path,
            403
        );

        $path = storage_path('app/public/' . $report->file_path);
        abort_unless(file_exists($path), 404, 'File PDF tidak ditemukan.');

        return response()->download($path, 'Progress_Report_' . str_replace(' ', '_', $student->name) . '.pdf');
    }

    // ---------------------------------------------------------------- SERTIFIKAT

    public function sertifikat()
    {
        $student = $this->student();

        $sertifikat = $student
            ? Certificate::approved()
                ->where('student_id', $student->id)
                ->with('enrollment.class')
                ->orderByDesc('reviewed_at')
                ->get()
            : collect();

        return view('siswa.sertifikat', [
            'title'      => 'Sertifikat',
            'subtitle'   => 'Download sertifikat kelulusan',
            'sertifikat' => $sertifikat,
        ]);
    }

    public function downloadSertifikat(Certificate $certificate)
    {
        $student = $this->student();

        abort_if(
            !$student
                || (int) $certificate->student_id !== (int) $student->id
                || $certificate->status !== 'Approved'
                || !$certificate->file_path,
            403
        );

        $path = storage_path('app/public/' . $certificate->file_path);
        abort_unless(file_exists($path), 404, 'File sertifikat tidak ditemukan.');

        return response()->download($path, 'Sertifikat_' . str_replace(' ', '_', $student->name) . '.pdf');
    }

    // ---------------------------------------------------------------- VIDEO

    public function video()
    {
        $student = $this->student();

        $videos = ($student && $this->isStudying($student))
            ? LearningVideo::visibleToLevels($this->activeLevelIds($student))
                ->with('level')
                ->latest()
                ->get()
            : collect();

        return view('siswa.video', [
            'title'    => 'Video Pembelajaran',
            'subtitle' => 'Tonton video dari Kurikulum',
            'videos'   => $videos,
        ]);
    }

    /**
     * Stream video lewat controller supaya hanya siswa yang berhak yang bisa menonton.
     * BinaryFileResponse mendukung Range request, jadi video bisa di-seek.
     */
    public function streamVideo(LearningVideo $video)
    {
        $student = $this->student();
        abort_if(!$student || !$this->isStudying($student), 403);

        $allowed = LearningVideo::visibleToLevels($this->activeLevelIds($student))
            ->whereKey($video->id)
            ->exists();

        abort_unless($allowed, 403);

        $path = storage_path('app/public/' . $video->file_path);
        abort_unless(is_file($path), 404, 'File video tidak ditemukan.');

        return response()->file($path, [
            'Content-Type'  => $video->mime_type ?: 'video/mp4',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}