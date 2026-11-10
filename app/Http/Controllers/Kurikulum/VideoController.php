<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ClassEnrollment;
use App\Models\LearningVideo;
use App\Models\Program;
use App\Models\ProgramCategory;
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
        'thumbnail.image'         => 'Thumbnail harus berupa file gambar.',
        'thumbnail.max'           => 'Ukuran thumbnail maksimal 2 MB.',
    ];

    private const YOUTUBE_ERROR = 'Link YouTube tidak valid. Contoh: https://www.youtube.com/watch?v=XXXXXXXXXXX atau https://youtu.be/XXXXXXXXXXX';

    public function index(Request $request)
    {
        $categories = ProgramCategory::with('program')->orderBy('category_name')->get();
        $programs = Program::orderBy('program_name')->get();
        $levels = ProgramLevel::with('program')->orderBy('level_name')->get();

        $selectedCategory = $request->query('category_id');

        $videos = LearningVideo::with(['level.program', 'category.program', 'uploader'])
            ->when($selectedCategory, fn ($q) => $q->where('category_id', $selectedCategory))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $allStudents = ClassEnrollment::where('status', 'Active')->distinct()->count('student_id');

        return view('kurikulum.video', compact('categories', 'programs', 'levels', 'videos', 'selectedCategory', 'allStudents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'category_id'      => ['nullable', 'integer', 'exists:program_categories,id'],
            'program_level_id' => ['nullable', 'integer', 'exists:program_levels,id'],
            'source_type'      => ['required', Rule::in(['file', 'youtube'])],
            'video'            => ['required_if:source_type,file', 'nullable', 'file', 'mimes:mp4,webm,mov', 'max:512000'],
            'youtube_url'      => ['required_if:source_type,youtube', 'nullable', 'string', 'max:255'],
            'thumbnail'        => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], self::MESSAGES);

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('video-thumbnails', 'public');
        }

        $base = [
            'category_id'      => $data['category_id'] ?? null,
            'program_level_id' => $data['program_level_id'] ?? null,
            'title'            => trim($data['title']),
            'description'      => $data['description'] ?? null,
            'source_type'      => $data['source_type'],
            'thumbnail_path'   => $thumbnailPath,
            'is_published'     => $request->boolean('is_published', true),
            'is_active'        => $request->boolean('is_active', true),
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

            return redirect()->route('kurikulum.video')->with('success', 'Video YouTube pembelajaran berhasil ditambahkan.');
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
            Storage::disk('public')->delete($path);
            if ($thumbnailPath) {
                Storage::disk('public')->delete($thumbnailPath);
            }
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan video. Silakan coba lagi.');
        }

        return redirect()->route('kurikulum.video')->with('success', 'Video pembelajaran berhasil diunggah.');
    }

    public function update(Request $request, LearningVideo $video)
    {
        $rules = [
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'category_id'      => ['nullable', 'integer', 'exists:program_categories,id'],
            'program_level_id' => ['nullable', 'integer', 'exists:program_levels,id'],
            'thumbnail'        => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];

        if ($video->source_type === 'youtube') {
            $rules['youtube_url'] = ['required', 'string', 'max:255'];
        }

        $data = $request->validate($rules, self::MESSAGES);

        $attrs = [
            'title'            => trim($data['title']),
            'description'      => $data['description'] ?? null,
            'category_id'      => $data['category_id'] ?? null,
            'program_level_id' => $data['program_level_id'] ?? null,
            'is_published'     => $request->boolean('is_published', true),
            'is_active'        => $request->boolean('is_active', true),
        ];

        if ($request->hasFile('thumbnail')) {
            if ($video->thumbnail_path) {
                Storage::disk('public')->delete($video->thumbnail_path);
            }
            $attrs['thumbnail_path'] = $request->file('thumbnail')->store('video-thumbnails', 'public');
        }

        if ($video->source_type === 'youtube') {
            $youtubeId = LearningVideo::extractYoutubeId($data['youtube_url']);

            if (!$youtubeId) {
                return back()->withErrors(['youtube_url' => self::YOUTUBE_ERROR]);
            }

            $attrs['youtube_id']  = $youtubeId;
            $attrs['youtube_url'] = LearningVideo::watchUrlFor($youtubeId);
        }

        $video->update($attrs);

        return back()->with('success', 'Video pembelajaran berhasil diperbarui.');
    }

    public function destroy(LearningVideo $video)
    {
        $filePath = $video->file_path;
        $thumbPath = $video->thumbnail_path;

        $video->delete();

        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
        if ($thumbPath && Storage::disk('public')->exists($thumbPath)) {
            Storage::disk('public')->delete($thumbPath);
        }

        return back()->with('success', 'Video pembelajaran berhasil dihapus.');
    }

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

    /** CRUD Kategori Program lewat modal */
    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'program_id'    => ['required', 'exists:programs,id'],
            'category_name' => ['required', 'string', 'max:100'],
            'min_age'       => ['nullable', 'integer', 'min:1'],
            'max_age'       => ['nullable', 'integer', 'min:1'],
        ]);

        ProgramCategory::create($data);

        return back()->with('success', 'Kategori program berhasil ditambahkan.');
    }

    public function destroyCategory(ProgramCategory $category)
    {
        $category->delete();

        return back()->with('success', 'Kategori program berhasil dihapus.');
    }
}
