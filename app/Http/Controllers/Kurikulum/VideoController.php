<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\LearningVideo;
use App\Models\ProgramLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Video Pembelajaran
    |--------------------------------------------------------------------------
    | Kurikulum mengunggah video. Video yang berstatus "dipublikasikan"
    | langsung bisa ditonton siswa (khusus level kelas aktif siswa, atau
    | semua siswa jika levelnya dikosongkan).
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $levels = ProgramLevel::with('program')
            ->orderBy('level_name')
            ->get();

        $videos = LearningVideo::with(['level.program', 'uploader'])
            ->latest()
            ->get();

        return view('kurikulum.video', compact('levels', 'videos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'program_level_id' => ['nullable', 'integer', 'exists:program_levels,id'],
            'video'            => ['required', 'file', 'mimes:mp4,webm,mov', 'max:512000'], // maks 500 MB
        ], [
            'video.required' => 'Pilih file video terlebih dahulu.',
            'video.mimes'    => 'Format video harus MP4, WebM, atau MOV.',
            'video.max'      => 'Ukuran video maksimal 500 MB.',
            'video.uploaded' => 'Upload gagal. Periksa upload_max_filesize dan post_max_size di php.ini.',
        ]);

        $file = $request->file('video');

        // Ambil info file SEBELUM disimpan
        $mime = $file->getMimeType();
        $size = $file->getSize();

        $path = $file->store('learning-videos', 'public');

        LearningVideo::create([
            'program_level_id' => $data['program_level_id'] ?? null,
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'file_path'        => $path,
            'mime_type'        => $mime,
            'file_size'        => $size,
            'is_published'     => $request->boolean('is_published'),
            'uploaded_by'      => Auth::id(),
        ]);

        return redirect()
            ->route('kurikulum.video')
            ->with('success', 'Video berhasil diunggah.');
    }

    public function update(Request $request, LearningVideo $video)
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'program_level_id' => ['nullable', 'integer', 'exists:program_levels,id'],
        ]);

        $video->update([
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'program_level_id' => $data['program_level_id'] ?? null,
            'is_published'     => $request->boolean('is_published'),
        ]);

        return back()->with('success', 'Video diperbarui.');
    }

    public function destroy(LearningVideo $video)
    {
        if ($video->file_path && Storage::disk('public')->exists($video->file_path)) {
            Storage::disk('public')->delete($video->file_path);
        }

        $video->delete();

        return back()->with('success', 'Video dihapus.');
    }
}