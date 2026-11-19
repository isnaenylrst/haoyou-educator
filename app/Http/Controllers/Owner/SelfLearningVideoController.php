<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\LearningVideo;
use App\Models\SelfLearningVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SelfLearningVideoController extends Controller
{
    public const MODULES = [
        'vocab'   => 'Belajar Vocab',
        'grammar' => 'Belajar Grammar',
    ];

    public function index(Request $request)
    {
        $module = $request->query('module');
        $module = array_key_exists((string) $module, self::MODULES) ? $module : null;

        $videos = SelfLearningVideo::with('uploader')
            ->when($module, fn ($q) => $q->where('module', $module))
            ->latest()
            ->get();

        $stats = [
            'total'   => SelfLearningVideo::count(),
            'vocab'   => SelfLearningVideo::where('module', 'vocab')->where('is_active', true)->exists(),
            'grammar' => SelfLearningVideo::where('module', 'grammar')->where('is_active', true)->exists(),
            'upload'  => SelfLearningVideo::where('source_type', 'upload')->count(),
        ];

        return view('owner.self-learning.index', [
            'videos'  => $videos,
            'modules' => self::MODULES,
            'module'  => $module,
            'stats'   => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'module'      => ['required', Rule::in(array_keys(self::MODULES))],
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'source_type' => ['required', Rule::in(['youtube', 'upload'])],
            'youtube_url' => ['nullable', 'required_if:source_type,youtube', 'string', 'max:255'],
            'video_file'  => ['nullable', 'required_if:source_type,upload', 'file', 'mimes:mp4,webm,mov', 'max:204800'],
        ], [
            'youtube_url.required_if' => 'Link YouTube wajib diisi.',
            'video_file.required_if'  => 'File video wajib dipilih.',
            'video_file.max'          => 'Ukuran video maksimal 200 MB. Untuk video lebih besar, gunakan link YouTube.',
            'video_file.mimes'        => 'Format video harus MP4, WEBM, atau MOV.',
        ]);

        $payload = [
            'module'      => $data['module'],
            'title'       => trim($data['title']),
            'description' => $data['description'] ?? null,
            'source_type' => $data['source_type'],
            'uploaded_by' => auth()->id(),
        ];

        if ($data['source_type'] === 'youtube') {
            $id = LearningVideo::extractYoutubeId($data['youtube_url']);
            if (!$id) {
                return back()->withInput()->withErrors(['youtube_url' => 'Link YouTube tidak valid.']);
            }
            $payload['youtube_url'] = trim($data['youtube_url']);
            $payload['youtube_id']  = $id;
        } else {
            $file = $request->file('video_file');
            $payload['file_path'] = $file->store('self-learning-videos', 'public');
            $payload['mime_type'] = $file->getMimeType();
            $payload['file_size'] = $file->getSize();
        }

        $payload['is_active'] = $request->boolean('is_active', true);

        $video = DB::transaction(function () use ($payload) {
            $video = SelfLearningVideo::create($payload);
            if ($video->is_active) {
                $this->deactivateOthers($video);
            }
            return $video;
        });

        return redirect()->route('owner.self-learning.index')
            ->with('success', 'Video berhasil ditambahkan' . ($video->is_active ? ' dan langsung tampil di panduan ' . $video->module_label . '.' : '.'));
    }

    public function update(Request $request, SelfLearningVideo $video)
    {
        $data = $request->validate([
            'module'      => ['required', Rule::in(array_keys(self::MODULES))],
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'youtube_url' => ['nullable', 'string', 'max:255'],
            'video_file'  => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:204800'],
        ], [
            'video_file.max'   => 'Ukuran video maksimal 200 MB. Untuk video lebih besar, gunakan link YouTube.',
            'video_file.mimes' => 'Format video harus MP4, WEBM, atau MOV.',
        ]);

        $payload = [
            'module'      => $data['module'],
            'title'       => trim($data['title']),
            'description' => $data['description'] ?? null,
        ];

        if ($request->hasFile('video_file')) {
            // ganti dengan file baru
            $this->deleteFile($video);
            $file = $request->file('video_file');
            $payload += [
                'source_type' => 'upload',
                'file_path'   => $file->store('self-learning-videos', 'public'),
                'mime_type'   => $file->getMimeType(),
                'file_size'   => $file->getSize(),
                'youtube_url' => null,
                'youtube_id'  => null,
            ];
        } elseif (filled($data['youtube_url'] ?? null)) {
            // ganti dengan link YouTube
            $id = LearningVideo::extractYoutubeId($data['youtube_url']);
            if (!$id) {
                return back()->withInput()->withErrors(['youtube_url' => 'Link YouTube tidak valid.']);
            }
            $this->deleteFile($video);
            $payload += [
                'source_type' => 'youtube',
                'youtube_url' => trim($data['youtube_url']),
                'youtube_id'  => $id,
                'file_path'   => null,
                'mime_type'   => null,
                'file_size'   => null,
            ];
        }

        DB::transaction(function () use ($video, $payload) {
            $video->update($payload);
            if ($video->is_active) {
                $this->deactivateOthers($video);
            }
        });

        return redirect()->route('owner.self-learning.index')->with('success', 'Video berhasil diperbarui.');
    }

    /** Aktifkan / nonaktifkan video. Mengaktifkan akan menonaktifkan video lain di modul yang sama. */
    public function toggle(SelfLearningVideo $video)
    {
        DB::transaction(function () use ($video) {
            $video->update(['is_active' => !$video->is_active]);
            if ($video->is_active) {
                $this->deactivateOthers($video);
            }
        });

        return back()->with('success', $video->is_active
            ? 'Video diaktifkan dan tampil ke siswa (' . $video->module_label . ').'
            : 'Video dinonaktifkan, tidak tampil ke siswa.');
    }

    public function destroy(SelfLearningVideo $video)
    {
        $this->deleteFile($video);
        $video->delete();

        return redirect()->route('owner.self-learning.index')->with('success', 'Video berhasil dihapus.');
    }

    /** Pratinjau file video untuk Owner (mendukung seek). */
    public function stream(SelfLearningVideo $video)
    {
        abort_unless($video->is_upload, 404);

        $path = storage_path('app/public/' . $video->file_path);
        abort_unless(is_file($path), 404, 'File video tidak ditemukan.');

        return response()->file($path, [
            'Content-Type'  => $video->mime_type ?: 'video/mp4',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function deactivateOthers(SelfLearningVideo $video): void
    {
        SelfLearningVideo::where('module', $video->module)
            ->where('id', '!=', $video->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    private function deleteFile(SelfLearningVideo $video): void
    {
        if ($video->file_path && Storage::disk('public')->exists($video->file_path)) {
            Storage::disk('public')->delete($video->file_path);
        }
    }
}