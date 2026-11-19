<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\SelfLearningVideo;

class SelfLearningVideoStreamController extends Controller
{
    /** Hanya video yang sedang aktif yang boleh diputar siswa. */
    public function stream(SelfLearningVideo $video)
    {
        abort_unless($video->is_active && $video->is_upload, 404);

        $path = storage_path('app/public/' . $video->file_path);
        abort_unless(is_file($path), 404, 'File video tidak ditemukan.');

        return response()->file($path, [
            'Content-Type'  => $video->mime_type ?: 'video/mp4',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}