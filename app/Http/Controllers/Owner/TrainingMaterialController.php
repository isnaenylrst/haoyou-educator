<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingMaterialRequest;
use App\Http\Requests\UpdateTrainingMaterialRequest;
use App\Models\TrainingMaterial;
use App\Models\TrainingMaterialRole;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TrainingMaterialController extends Controller
{
    public const PRESET_CATEGORIES = [
        'Pedagogik & Metode Mengajar',
        'Standar Kurikulum & Silabus',
        'SOP & Administrasi Pengajar',
        'Operasional & Administrasi Lembaga',
        'Penguasaan Bahasa Mandarin',
        'Teknologi & Media Pembelajaran',
        'Pengembangan Karakter & Etika',
        'Lainnya',
    ];

    /**
     * Tampilkan daftar materi training untuk Owner (Full Access)
     * Memisahkan galeri video dan daftar dokumen/SOP, mendukung filter role, level, type, category, dan pencarian.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', TrainingMaterial::class);

        $roleTab  = $request->query('role', 'All'); // All, Admin, Curriculum, Teacher
        $typeTab  = $request->query('type', 'all'); // all, document, video
        $level    = $request->query('level');      // Dasar, Menengah, Lanjutan, Umum
        $category = $request->query('category');
        $q        = trim($request->query('q', ''));

        $query = TrainingMaterial::with(['uploader', 'roles'])->latest();

        if ($roleTab && $roleTab !== 'All') {
            $query->forRole($roleTab);
        }

        if ($typeTab === 'video') {
            $query->where('type', 'video');
        } elseif ($typeTab === 'document') {
            $query->where('type', 'document');
        }

        if ($level) {
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

        $existingCategories = TrainingMaterial::distinct()->pluck('category')->filter()->values()->toArray();
        $categories = array_values(array_unique(array_merge(self::PRESET_CATEGORIES, $existingCategories)));

        // Data riil untuk kartu ringkasan
        $stats = [
            'total'            => TrainingMaterial::count(),
            'admin'            => TrainingMaterial::forRole('Admin')->count(),
            'curriculum'       => TrainingMaterial::forRole('Curriculum')->count(),
            'teacher'          => TrainingMaterial::forRole('Teacher')->count(),
            'with_video'       => TrainingMaterial::where('type', 'video')->count(),
            'with_file'        => TrainingMaterial::where('type', 'document')->count(),
            'total_categories' => count($categories),
        ];

        return view('owner.training', compact(
            'materials',
            'categories',
            'stats',
            'roleTab',
            'typeTab',
            'level',
            'category',
            'q'
        ));
    }

    /**
     * Simpan materi training baru oleh Owner
     */
    public function store(StoreTrainingMaterialRequest $request)
    {
        Gate::authorize('create', TrainingMaterial::class);

        $validated = $request->validated();

        $material = DB::transaction(function () use ($request, $validated) {
            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('training-materials/docs', 'public');
            }

            $videoPath = null;
            if ($request->hasFile('video_file')) {
                $videoPath = $request->file('video_file')->store('training-materials/videos', 'public');
            }

            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('training-materials/thumbnails', 'public');
            }

            $targetRoles = array_values(array_unique($validated['target_roles'] ?? []));
            $legacyTargetRole = count($targetRoles) === 1 ? $targetRoles[0] : (count($targetRoles) === 3 ? 'All' : implode(',', $targetRoles));

            $mat = TrainingMaterial::create([
                'title'          => trim($validated['title']),
                'category'       => trim($validated['category']),
                'target_role'    => $legacyTargetRole,
                'level'          => $validated['level'],
                'type'           => $validated['type'],
                'version'        => !empty($validated['version']) ? trim($validated['version']) : 'v1.0',
                'description'    => $validated['description'] ?? null,
                'thumbnail_path' => $thumbnailPath,
                'file_path'      => $filePath,
                'video_url'      => !empty($validated['video_url']) ? trim($validated['video_url']) : null,
                'video_path'     => $videoPath,
                'uploaded_by'    => Auth::id(),
            ]);

            // Simpan ke pivot/tabel roles
            foreach ($targetRoles as $r) {
                TrainingMaterialRole::create([
                    'training_material_id' => $mat->id,
                    'role'                 => $r,
                ]);
            }

            // Notifikasi HANYA ke pengguna dari peran yang dituju
            $levelNames = [];
            foreach ($targetRoles as $r) {
                if ($r === 'Admin') {
                    $levelNames[] = 'Admin';
                } elseif ($r === 'Curriculum') {
                    $levelNames[] = 'Curriculum';
                    $levelNames[] = 'Kurikulum';
                } elseif ($r === 'Teacher') {
                    $levelNames[] = 'Teacher';
                    $levelNames[] = 'Guru';
                }
            }
            $levelNames = array_unique($levelNames);

            $recipients = User::query()
                ->whereHas('level', function ($q) use ($levelNames) {
                    $q->whereIn('nama_level', $levelNames);
                })
                ->get();

            foreach ($recipients as $recipient) {
                $roleName = $recipient->level?->nama_level;
                $targetUrl = match ($roleName) {
                    'Admin'                   => url('/admin/training'),
                    'Curriculum', 'Kurikulum' => url('/kurikulum/training'),
                    default                   => url('/guru/training'),
                };

                $recipient->notify(new AppNotification(
                    title: 'Materi Training Baru: ' . $mat->title,
                    message: "Owner mengunggah materi training [{$mat->level} - {$mat->category}]: \"{$mat->title}\". Silakan pelajari materi.",
                    url: $targetUrl,
                    type: 'training',
                    icon: 'fa-graduation-cap'
                ));
            }

            return $mat;
        });

        return redirect()->route('owner.training.index')
            ->with('success', 'Materi training berhasil diunggah dan notifikasi telah dikirim ke peran terkait (' . implode(', ', $material->target_roles_list) . ').');
    }

    /**
     * Perbarui materi training oleh Owner
     */
    public function update(UpdateTrainingMaterialRequest $request, $id)
    {
        $material = TrainingMaterial::findOrFail($id);
        Gate::authorize('update', $material);

        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $material) {
            $targetRoles = array_values(array_unique($validated['target_roles'] ?? []));
            $legacyTargetRole = count($targetRoles) === 1 ? $targetRoles[0] : (count($targetRoles) === 3 ? 'All' : implode(',', $targetRoles));

            $data = [
                'title'       => trim($validated['title']),
                'category'    => trim($validated['category']),
                'target_role' => $legacyTargetRole,
                'level'       => $validated['level'],
                'type'        => $validated['type'],
                'version'     => !empty($validated['version']) ? trim($validated['version']) : $material->version,
                'description' => $validated['description'] ?? null,
                'video_url'   => !empty($validated['video_url']) ? trim($validated['video_url']) : null,
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

            if ($request->hasFile('thumbnail')) {
                if ($material->thumbnail_path && Storage::disk('public')->exists($material->thumbnail_path)) {
                    Storage::disk('public')->delete($material->thumbnail_path);
                }
                $data['thumbnail_path'] = $request->file('thumbnail')->store('training-materials/thumbnails', 'public');
            }

            $material->update($data);

            // Sync peran di tabel training_material_roles
            $material->roles()->delete();
            foreach ($targetRoles as $r) {
                TrainingMaterialRole::create([
                    'training_material_id' => $material->id,
                    'role'                 => $r,
                ]);
            }
        });

        return redirect()->route('owner.training.index')
            ->with('success', 'Materi training berhasil diperbarui.');
    }

    /**
     * Hapus materi training oleh Owner (File terhapus dari storage)
     */
    public function destroy($id)
    {
        $material = TrainingMaterial::findOrFail($id);
        Gate::authorize('delete', $material);

        DB::transaction(function () use ($material) {
            if ($material->file_path && Storage::disk('public')->exists($material->file_path)) {
                Storage::disk('public')->delete($material->file_path);
            }

            if ($material->video_path && Storage::disk('public')->exists($material->video_path)) {
                Storage::disk('public')->delete($material->video_path);
            }

            if ($material->thumbnail_path && Storage::disk('public')->exists($material->thumbnail_path)) {
                Storage::disk('public')->delete($material->thumbnail_path);
            }

            $material->roles()->delete();
            $material->delete();
        });

        return redirect()->route('owner.training.index')
            ->with('success', 'Materi training beserta berkas terkait berhasil dihapus dari sistem.');
    }

    /**
     * Unduh file dokumen lampiran
     */
    public function download($id)
    {
        $material = TrainingMaterial::findOrFail($id);
        Gate::authorize('view', $material);

        abort_unless($material->file_path && Storage::disk('public')->exists($material->file_path), 404, 'File lampiran tidak ditemukan.');

        return Storage::disk('public')->download($material->file_path, $material->file_name);
    }

    /**
     * Preview inline PDF di browser
     */
    public function preview($id)
    {
        $material = TrainingMaterial::findOrFail($id);
        Gate::authorize('view', $material);

        abort_unless($material->file_path && Storage::disk('public')->exists($material->file_path), 404, 'File dokumen tidak ditemukan.');

        return Storage::disk('public')->response(
            $material->file_path,
            $material->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Stream file video atau redirect ke video url
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
