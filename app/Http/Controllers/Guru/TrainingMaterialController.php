<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\TrainingMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrainingMaterialController extends Controller
{
    /**
     * Tampilkan daftar materi training untuk Guru (Read-Only)
     */
    public function index(Request $request)
    {
        $type     = $request->query('type', 'all');
        $level    = $request->query('level');
        $category = $request->query('category');
        $q        = trim($request->query('q', ''));

        // Guru hanya melihat materi untuk Teacher atau All
        $query = TrainingMaterial::with('uploader')
            ->whereIn('target_role', ['Teacher', 'Guru', 'All'])
            ->latest();

        if ($type && in_array($type, ['document', 'video'])) {
            $query->where('type', $type);
        }

        if ($level && in_array($level, ['Umum', 'Dasar', 'Menengah', 'Lanjutan'])) {
            $query->where('level', $level);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($q !== '') {
            $search = '%' . $q . '%';
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('category', 'like', $search);
            });
        }

        $materials = $query->paginate(12)->withQueryString();
        $categories = TrainingMaterial::whereIn('target_role', ['Teacher', 'Guru', 'All'])
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values()
            ->toArray();

        return view('guru.training', compact('materials', 'categories', 'type', 'level', 'category', 'q'));
    }

    /**
     * Unduh file dokumen lampiran materi
     */
    public function download($id)
    {
        $material = TrainingMaterial::findOrFail($id);

        abort_unless($material->file_path && Storage::disk('public')->exists($material->file_path), 404, 'File lampiran tidak ditemukan.');

        return Storage::disk('public')->download($material->file_path, $material->file_name);
    }

    /**
     * Putar / stream video materi
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

