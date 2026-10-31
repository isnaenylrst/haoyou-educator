<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\TrainingMaterial;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TrainingMaterialController extends Controller
{
    public const PRESET_CATEGORIES = [
        'Pedagogik & Metode Mengajar',
        'Standar Kurikulum & Silabus',
        'SOP & Administrasi Pengajar',
        'Penguasaan Bahasa Mandarin',
        'Teknologi & Media Pembelajaran',
        'Pengembangan Karakter & Etika',
        'Lainnya',
    ];

    /**
     * Tampilkan daftar materi training untuk Owner (Full Access)
     */
    public function index(Request $request)
    {
        $query = TrainingMaterial::with('uploader')->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('q')) {
            $search = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        $materials = $query->paginate(12)->withQueryString();

        // Kategori dinamis dari database digabung dengan preset
        $existingCategories = TrainingMaterial::distinct()->pluck('category')->filter()->values()->toArray();
        $categories = array_values(array_unique(array_merge(self::PRESET_CATEGORIES, $existingCategories)));

        $stats = [
            'total'           => TrainingMaterial::count(),
            'with_video'      => TrainingMaterial::where(function ($q) {
                $q->whereNotNull('video_url')->orWhereNotNull('video_path');
            })->count(),
            'with_file'       => TrainingMaterial::whereNotNull('file_path')->count(),
            'total_categories'=> count($categories),
        ];

        return view('owner.training', compact('materials', 'categories', 'stats'));
    }

    /**
     * Simpan materi training baru oleh Owner
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'file'        => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,rar', 'max:51200'],
            'video_file'  => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:102400'],
            'video_url'   => ['nullable', 'url', 'max:255'],
        ], [
            'title.required'    => 'Judul materi training wajib diisi.',
            'category.required' => 'Kategori materi wajib dipilih/diisi.',
            'file.max'          => 'Ukuran file dokumen maksimal 50 MB.',
            'video_file.max'    => 'Ukuran file video maksimal 100 MB.',
            'video_url.url'     => 'Format link video harus berupa URL valid.',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('training-materials/docs', 'public');
        }

        $videoPath = null;
        if ($request->hasFile('video_file')) {
            $videoPath = $request->file('video_file')->store('training-materials/videos', 'public');
        }

        $material = TrainingMaterial::create([
            'title'       => trim($validated['title']),
            'category'    => trim($validated['category']),
            'description' => $validated['description'] ?? null,
            'file_path'   => $filePath,
            'video_url'   => $validated['video_url'] ? trim($validated['video_url']) : null,
            'video_path'  => $videoPath,
            'uploaded_by' => Auth::id(),
        ]);

        // Notifikasi ke seluruh Guru dan Kurikulum
        $recipients = User::query()
            ->whereHas('level', function ($q) {
                $q->whereIn('nama_level', ['Teacher', 'Curriculum', 'Guru', 'Kurikulum']);
            })
            ->get();

        foreach ($recipients as $recipient) {
            $isKurikulum = in_array($recipient->level?->nama_level, ['Curriculum', 'Kurikulum']);
            $targetUrl = $isKurikulum ? url('/kurikulum/training') : url('/guru/training');

            $recipient->notify(new AppNotification(
                title: 'Materi Training Baru: ' . $material->title,
                message: 'Owner mengunggah materi training baru: "' . $material->title . '" (' . $material->category . '). Silakan pelajari materi.',
                url: $targetUrl,
                type: 'training',
                icon: 'fa-graduation-cap'
            ));
        }

        return redirect()->route('owner.training.index')->with('success', 'Materi training berhasil diunggah dan notifikasi telah dikirim ke Guru & Kurikulum.');
    }

    /**
     * Perbarui materi training oleh Owner
     */
    public function update(Request $request, $id)
    {
        $material = TrainingMaterial::findOrFail($id);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'file'        => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,rar', 'max:51200'],
            'video_file'  => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:102400'],
            'video_url'   => ['nullable', 'url', 'max:255'],
        ]);

        $data = [
            'title'       => trim($validated['title']),
            'category'    => trim($validated['category']),
            'description' => $validated['description'] ?? null,
            'video_url'   => $validated['video_url'] ? trim($validated['video_url']) : null,
        ];

        if ($request->hasFile('file')) {
            if ($material->file_path && Storage::disk('public')->exists($material->file_path)) {
                Storage::disk('public')->delete($material->file_path);
            }
            $data['file_path'] = $request->file('file')->store('training-materials/docs', 'public');
        }

        if ($request->hasFile('video_file')) {
            if ($material->video_path && Storage::disk('public')->exists($material->video_path)) {
                Storage::disk('public')->delete($material->video_path);
            }
            $data['video_path'] = $request->file('video_file')->store('training-materials/videos', 'public');
        }

        $material->update($data);

        return redirect()->route('owner.training.index')->with('success', 'Materi training berhasil diperbarui.');
    }

    /**
     * Hapus materi training oleh Owner
     */
    public function destroy($id)
    {
        $material = TrainingMaterial::findOrFail($id);

        if ($material->file_path && Storage::disk('public')->exists($material->file_path)) {
            Storage::disk('public')->delete($material->file_path);
        }

        if ($material->video_path && Storage::disk('public')->exists($material->video_path)) {
            Storage::disk('public')->delete($material->video_path);
        }

        $material->delete();

        return redirect()->route('owner.training.index')->with('success', 'Materi training berhasil dihapus.');
    }

    /**
     * Unduh file dokumen lampiran
     */
    public function download($id)
    {
        $material = TrainingMaterial::findOrFail($id);

        abort_unless($material->file_path && Storage::disk('public')->exists($material->file_path), 404, 'File lampiran tidak ditemukan.');

        return Storage::disk('public')->download($material->file_path, $material->file_name);
    }

    /**
     * Stream file video atau redirect ke video url
     */
    public function streamVideo($id)
    {
        $material = TrainingMaterial::findOrFail($id);

        if ($material->video_path && Storage::disk('public')->exists($material->video_path)) {
            $fullPath = Storage::disk('public')->path($material->video_path);
            return response()->file($fullPath, [
                'Content-Type' => 'video/mp4',
            ]);
        }

        if ($material->video_url) {
            return redirect()->away($material->video_url);
        }

        abort(404, 'Video tidak ditemukan.');
    }
}

