<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\LearningVideo;
use App\Models\ProgramCategory;
use Illuminate\Support\Facades\Auth;

class VideoController extends Controller
{
    private function teacher()
    {
        return Auth::user()->teacher;
    }

    public function index()
    {
        $teacher = $this->teacher();
        abort_unless($teacher, 403, 'Akses khusus guru.');

        // Ambil kelas yang diampu guru ini
        $classes = ClassModel::where('teacher_id', $teacher->id)
            ->whereIn('status', ['Open', 'Running'])
            ->with(['level', 'programPackage.program'])
            ->get();

        $levelIds = $classes->pluck('level_id')->filter()->unique()->values()->all();

        // Kategori program yang relevan dengan level kelas guru
        $categoryIds = ProgramCategory::whereHas('levels', fn ($q) => $q->whereIn('id', $levelIds))
            ->pluck('id')->values()->all();

        // Video yang relevan untuk kelas guru, atau video umum
        $videos = LearningVideo::visibleToCategoriesAndLevels($categoryIds, $levelIds)
            ->with(['category', 'level.program'])
            ->latest()
            ->get();

        // Kelompokkan video per kategori program
        $groupedVideos = $videos->groupBy(function ($v) {
            return $v->category?->category_name ?? 'Umum / Semua Program';
        });

        return view('guru.video', [
            'title'         => 'Video Pembelajaran',
            'groupedVideos' => $groupedVideos,
            'classes'       => $classes,
        ]);
    }

    public function stream(LearningVideo $video)
    {
        $teacher = $this->teacher();
        abort_unless($teacher, 403);

        if ($video->source_type === 'youtube') {
            return redirect()->away($video->watch_url);
        }

        abort_unless($video->file_path, 404);

        $path = storage_path('app/public/' . $video->file_path);
        abort_unless(is_file($path), 404, 'File video tidak ditemukan.');

        return response()->file($path, [
            'Content-Type'  => $video->mime_type ?: 'video/mp4',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}

