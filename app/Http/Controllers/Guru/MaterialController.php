<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    /*
    | Guru hanya BISA LIHAT & DOWNLOAD materi + silabus yang sudah
    | disiapkan Kurikulum. Tidak ada upload/edit/hapus di sisi Guru.
    | Guru melihat materi untuk program/kelas yang dia ajar plus materi "Umum".
    */

    public function index(Request $request)
    {
        $user = Auth::user();
        $teacher = $user?->teacher;

        // Ambil kelas yang diajar guru
        $teacherClasses = $teacher
            ? $teacher->classes()->with(['level.category', 'programPackage.category'])->get()
            : collect();

        // Ambil category_id / program_id & level_id yang diajar guru
        $taughtProgramIds = $teacherClasses->flatMap(function ($c) {
            return [
                $c->level?->category_id,
                $c->programPackage?->category_id,
            ];
        })->filter()->unique()->values();

        $taughtLevelIds = $teacherClasses->pluck('level_id')->filter()->unique()->values();

        $query = Material::forGuru()->with([
            'program',
            'level.category',
            'level.program',
            'uploader',
            'vocabularies',
        ]);

        // Guru melihat materi untuk program/kelas yang dia ajar plus materi "Umum"
        $query->where(function ($q) use ($taughtProgramIds, $taughtLevelIds) {
            $q->whereNull('program_id'); // Materi "Umum"

            if ($taughtProgramIds->isNotEmpty()) {
                $q->orWhereIn('program_id', $taughtProgramIds);
            }

            if ($taughtLevelIds->isNotEmpty()) {
                $q->orWhereIn('level_id', $taughtLevelIds);
            }
        });

        // Filter pencarian judul
        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('syllabus', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Filter program spesifik dari query request
        if ($request->filled('program')) {
            $progFilter = $request->query('program');
            if ($progFilter === 'Umum') {
                $query->whereNull('program_id');
            } else {
                $query->whereHas('program', fn ($pq) => $pq->where('category_name', $progFilter));
            }
        }

        $materials = $query
            ->orderByRaw('program_id IS NULL DESC, program_id ASC')
            ->orderBy('level_id')
            ->orderBy('meeting_number')
            ->get();

        // Kelompokkan per program (label program atau "Umum")
        $groupedMaterials = $materials->groupBy(fn ($m) => $m->program_name);

        // Daftar program yang tersedia untuk tab/filter
        $availablePrograms = $materials
            ->map(fn ($m) => $m->program_name)
            ->unique()
            ->values();

        return view('guru.materi', compact('materials', 'groupedMaterials', 'availablePrograms'));
    }

    public function show(Material $material)
    {
        abort_unless($material->audience === 'Guru', 404);
        abort_unless($material->file_exists, 404, 'File tidak ditemukan di server.');

        // PDF dibuka di browser; Word/PowerPoint otomatis diunduh
        if (!$material->is_pdf) {
            return $this->download($material);
        }

        return response()->file(
            storage_path('app/public/' . $material->material_file_path),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function download(Material $material)
    {
        abort_unless($material->audience === 'Guru', 404);
        if (!$material->file_exists) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        $name = (Str::slug($material->title) ?: 'materi-' . $material->id)
            . '.' . $material->file_extension;

        return response()->download(storage_path('app/public/' . $material->material_file_path), $name);
    }
}
