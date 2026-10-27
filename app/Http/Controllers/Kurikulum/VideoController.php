<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ClassEnrollment;
use App\Models\LearningVideo;
use App\Models\ProgramLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VideoController extends Controller
{
    private const MESSAGES = [
        'video.required_if'       => 'Pilih file video terlebih dahulu.',
        'video.mimes'             => 'Format video harus MP4, WebM, atau MOV.',
        'video.max'               => 'Ukuran video maksimal 500 MB.',
        'video.uploaded'          => 'Upload gagal. Periksa upload_max_filesize dan post_max_size di php.ini.',
        'youtube_url.required_if' => 'Tempel link YouTube terlebih dahulu.',
        'youtube_url.required'    => 'Tempel link YouTube terlebih dahulu.',
    ];

    private const YOUTUBE_ERROR = 'Link YouTube tidak valid. Contoh: https://www.youtube.com/watch?v=XXXXXXXXXXX atau https://youtu.be/XXXXXXXXXXX';

    public function index()
    {
        $levels = ProgramLevel::with('program')->orderBy('level_name')->get();
        $videos = LearningVideo::with(['level.program', 'uploader'])->latest()->get();

        // Jumlah siswa aktif per level (= siswa yang berhak menonton video level itu)
        $levelCounts = ClassEnrollment::query()
            ->join('classes', 'classes.id', '=', 'class_enrollments.class_id')
            ->where('class_enrollments.status', 'Active')
            ->groupBy('classes.level_id')
            ->selectRaw('classes.level_id as level_id, COUNT(DISTINCT class_enrollments.student_id) as total')
            ->pluck('total', 'level_id');

        $allStudents = ClassEnrollment::where('status', 'Active')->distinct()->count('student_id');

        return view('kurikulum.video', compact('levels', 'videos', 'levelCounts', 'allStudents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'program_level_id' => ['nullable', 'integer', 'exists:program_levels,id'],
            'source_type'      => ['required', Rule::in(['file', 'youtube'])],
            'video'            => ['required_if:source_type,file', 'nullable', 'file', 'mimes:mp4,webm,mov', 'max:512000'],
            'youtube_url'      => ['required_if:source_type,youtube', 'nullable', 'string', 'max:255'],
        ], self::MESSAGES);

        $base = [
            'program_level_id' => $data['program_level_id'] ?? null,
            'title'            => trim($data['title']),
            'description'      => $data['description'] ?? null,
            'source_type'      => $data['source_type'],
            'is_published'     => $request->boolean('is_published'),
            'uploaded_by'      => Auth::id(),
        ];

        // ---- Link YouTube
        if ($data['source_type'] === 'youtube') {
            $youtubeId = LearningVideo::extractYoutubeId($data['youtube_url']);

            if (!$youtubeId) {
                return back()->withInput()->withErrors(['youtube_url' => self::YOUTUBE_ERROR]);
            }

            LearningVideo::create($base + [
                'youtube_id'  => $youtubeId,
                'youtube_url' => LearningVideo::watchUrlFor($youtubeId),
            ]);

            return redirect()->route('kurikulum.video')->with('success', 'Link YouTube berhasil ditambahkan.');
        }

        // ---- Upload file
        $file = $request->file('video');
        $mime = $file->getMimeType();
        $size = $file->getSize();
        $path = $file->store('learning-videos', 'public');

        try {
            LearningVideo::create($base + [
                'file_path' => $path,
                'mime_type' => $mime,
                'file_size' => $size,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);   // jangan sisakan file yatim
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan video. Silakan coba lagi.');
        }

        return redirect()->route('kurikulum.video')->with('success', 'Video berhasil diunggah.');
    }

    public function update(Request $request, LearningVideo $video)
    {
        $rules = [
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'program_level_id' => ['nullable', 'integer', 'exists:program_levels,id'],
        ];

        if ($video->source_type === 'youtube') {
            $rules['youtube_url'] = ['required', 'string', 'max:255'];
        }

        $data = $request->validate($rules, self::MESSAGES);

        $attrs = [
            'title'            => trim($data['title']),
            'description'      => $data['description'] ?? null,
            'program_level_id' => $data['program_level_id'] ?? null,
            'is_published'     => $request->boolean('is_published'),
        ];

        if ($video->source_type === 'youtube') {
            $youtubeId = LearningVideo::extractYoutubeId($data['youtube_url']);

            if (!$youtubeId) {
                return back()->withErrors(['youtube_url' => self::YOUTUBE_ERROR]);
            }

            $attrs['youtube_id']  = $youtubeId;
            $attrs['youtube_url'] = LearningVideo::watchUrlFor($youtubeId);
        }

        $video->update($attrs);

        return back()->with('success', 'Video diperbarui.');
    }

    public function destroy(LearningVideo $video)
    {
        $path = $video->file_path;
        $video->delete();

        // hanya video berbentuk file yang punya file di storage
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'Video dihapus.');
    }

    /** Pratinjau video file untuk Kurikulum (butuh login, mendukung seek). */
    public function stream(LearningVideo $video)
    {
        if ($video->source_type === 'youtube') {
            return redirect()->away($video->watch_url);
        }

        abort_unless($video->file_path, 404);

        $path = storage_path('app/public/' . $video->file_path);
        abort_unless(is_file($path), 404, 'File video tidak ditemukan.');

        return response()->file($path, [
            'Content-Type' => $video->mime_type ?: 'video/mp4',
        ]);
    }
}