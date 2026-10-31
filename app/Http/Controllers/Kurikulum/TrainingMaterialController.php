<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\TrainingMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrainingMaterialController extends Controller
{
    /**
     * Tampilkan daftar materi training untuk Kurikulum (Read-Only)
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
        $categories = TrainingMaterial::distinct()->pluck('category')->filter()->values()->toArray();

        return view('kurikulum.training', compact('materials', 'categories'));
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

