<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    /*
    | Guru hanya BISA LIHAT & DOWNLOAD materi + silabus yang sudah
    | disiapkan Kurikulum. Tidak ada upload/edit/hapus di sisi Guru.
    | SEMUA guru dapat melihat SEMUA materi audience = Guru dari seluruh program.
    */

    public function index(Request $request)
    {
        $user = Auth::user();
        $teacher = $user?->teacher;

        // Ambil kelas yang diajar guru ini untuk penanda "Kelas Anda"
        $teacherClasses = $teacher
            ? $teacher->classes()->with(['level.category', 'programPackage.category'])->get()
            : collect();

        $taughtProgramIds = $teacherClasses->flatMap(function ($c) {
            return [
                $c->level?->category_id,
                $c->programPackage?->category_id,
            ];
        })->filter()->unique()->values()->all();

        $taughtLevelIds = $teacherClasses->pluck('level_id')->filter()->unique()->values()->all();

        // 1. Program-program yang memiliki materi Guru (untuk tab/chip & dropdown)
        // Hitung jumlah materi Guru per program_id (termasuk program_id NULL = Umum)
        $materialCountsByProgram = Material::forGuru()
            ->selectRaw('program_id, COUNT(*) as total')
            ->groupBy('program_id')
            ->get();

        $hasUmum = $materialCountsByProgram->contains(fn ($row) => is_null($row->program_id));
        $umumCount = (int) ($materialCountsByProgram->firstWhere(fn ($row) => is_null($row->program_id))?->total ?? 0);

        $assignedProgramIds = $materialCountsByProgram->pluck('program_id')->filter()->values()->all();

        // Ambil data ProgramCategory yang punya materi Guru
        $programCategories = ProgramCategory::with(['levels' => fn ($lq) => $lq->orderBy('sort_order')])
            ->whereIn('id', $assignedProgramIds)
            ->get();

        // Bentuk list program tabs: program yang diajar guru diutamakan lebih dulu, lalu program lain, lalu Umum
        $programTabs = $programCategories->map(function ($cat) use ($materialCountsByProgram, $taughtProgramIds) {
            $count = (int) ($materialCountsByProgram->firstWhere('program_id', $cat->id)?->total ?? 0);
            $isTaught = in_array($cat->id, $taughtProgramIds, true);
            return [
                'id'        => (string) $cat->id,
                'name'      => $cat->category_name,
                'count'     => $count,
                'is_taught' => $isTaught,
                'levels'    => $cat->levels->map(fn ($lvl) => [
                    'id'   => $lvl->id,
                    'name' => $lvl->level_name,
                ])->toArray(),
            ];
        })->sortByDesc('is_taught')->values();

        // Tab "Umum" jika ada materi umum
        if ($hasUmum || $umumCount > 0) {
            $programTabs->push([
                'id'        => 'umum',
                'name'      => 'Umum',
                'count'     => $umumCount,
                'is_taught' => false,
                'levels'    => [],
            ]);
        }

        $totalGuruMaterials = Material::forGuru()->count();

        // 2. Query data materi untuk guru (SEMUA program yang audience = Guru)
        $query = Material::forGuru()->with([
            'program',
            'level.category',
            'level.program',
            'uploader',
            'vocabularies',
        ]);

        // Filter Program
        $selectedProgram = $request->query('program');
        if ($selectedProgram !== null && $selectedProgram !== '') {
            if ($selectedProgram === 'umum') {
                $query->whereNull('program_id');
            } else {
                $query->where('program_id', $selectedProgram);
            }
        }

        // Filter Level
        $selectedLevel = $request->query('level_id');
        if ($request->filled('level_id')) {
            $query->where('level_id', (int) $selectedLevel);
        }

        // Pencarian Judul / Silabus / Deskripsi / Pertemuan
        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('syllabus', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");

                if (ctype_digit($term)) {
                    $q->orWhere('meeting_number', (int) $term);
                }
            });
        }

        // Urutan:
        // Program yang diajar guru ditaruh di awal, lalu program lain, lalu Umum (program_id IS NULL).
        // Di dalam tiap program: urut level_id lalu meeting_number.
        $materials = $query
            ->orderByRaw('program_id IS NULL ASC') // Program spesifik dulu, baru Umum
            ->orderBy('program_id')
            ->orderByRaw('level_id IS NULL ASC')
            ->orderBy('level_id')
            ->orderBy('meeting_number')
            ->paginate(50)
            ->withQueryString();

        // Kelompokkan per Program -> per Level untuk struktur tampilan accordion
        // Urutan kelompok program: program yang diajar guru di paling atas
        $groupedMaterials = $materials->getCollection()
            ->groupBy(function ($m) {
                return $m->program_id ? (string) $m->program_id : 'umum';
            })
            ->sortBy(function ($items, $progKey) use ($taughtProgramIds) {
                if ($progKey === 'umum') {
                    return 9999;
                }
                $isTaught = in_array((int) $progKey, $taughtProgramIds, true);
                return $isTaught ? 0 : 1;
            });

        return view('guru.materi', compact(
            'materials',
            'groupedMaterials',
            'programTabs',
            'selectedProgram',
            'selectedLevel',
            'totalGuruMaterials',
            'taughtProgramIds',
            'taughtLevelIds'
        ));
    }

    public function show(Material $material)
    {
        abort_unless($material->audience === 'Guru', 404);
        abort_unless($material->file_exists, 404, 'File tidak ditemukan di server.');

        // PDF dibuka di browser; Word/PowerPoint otomatis diunduh
        if (!$material->is_pdf) {
            return $this->download($material);
        }

        return Storage::disk('public')->response(
            $material->material_file_path,
            null,
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

        return Storage::disk('public')->download($material->material_file_path, $name);
    }
}

