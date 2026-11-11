<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TrainingMaterialController extends Controller
{
    /**
     * Tampilkan daftar materi training untuk Admin (Read-Only)
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', TrainingMaterial::class);

        $type     = $request->query('type', 'all'); // 'all', 'document', 'video'
        $level    = $request->query('level');
        $category = $request->query('category');
        $q        = trim($request->query('q', ''));

        // Admin hanya melihat materi yang ditujukan untuk Admin
        $query = TrainingMaterial::with(['uploader', 'roles'])
            ->forRole('Admin')
            ->latest();

        if ($type && in_array($type, ['document', 'video'], true)) {
            $query->where('type', $type);
        }

        if ($level && in_array($level, ['Umum', 'Dasar', 'Menengah', 'Lanjutan'], true)) {
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
        $categories = TrainingMaterial::forRole('Admin')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values()
            ->toArray();

        return view('admin.training', compact('materials', 'categories', 'type', 'level', 'category', 'q'));
    }

    /**
     * Unduh file dokumen lampiran materi
     */
    public function download($id)
    {
        $material = TrainingMaterial::findOrFail($id);
        Gate::authorize('view', $material);

        abort_unless($material->file_path && Storage::disk('public')->exists($material->file_path), 404, 'File lampiran tidak ditemukan.');

        return Storage::disk('public')->download($material->file_path, $material->file_name);
    }

    /**
     * Putar / stream video materi
     */
    public function streamVideo($id)
    {
        $material = TrainingMaterial::findOrFail($id);
        Gate::authorize('view', $material);

        if ($material->video_path && Storage::disk('public')->exists($material->video_path)) {
            return Storage::disk('public')->response(
                $material->video_path,
                null,
                ['Content-Type' => 'video/mp4']
            );
        }

        if ($material->video_url) {
            return redirect()->away($material->video_url);
        }

        abort(404, 'Video tidak ditemukan.');
    }
}
