<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AchievementLevel;
use App\Models\ClassLearningMaterial;
use App\Models\ClassModel;
use App\Models\ProgramLevel;
use App\Models\ReportCompetency;
use Illuminate\Http\Request;

class KompetensiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Kompetensi & Learning Materials Progress Report
    |--------------------------------------------------------------------------
    | Kurikulum menetapkan Learning Materials per kelas (contoh Kelas Feixiang 1A)
    | atau kompetensi per level. Guru tinggal mengisi:
    | Achievement Level (Sufficient, Proficient, Excellent) + deskripsi,
    | serta bagian Assessment (Score, Grade, Note).
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $levelFilter = $request->get('level');
        $classFilter = $request->get('class_id');

        $levels = ProgramLevel::with(['program', 'category'])
            ->orderBy('level_name')
            ->get();

        $classes = ClassModel::with(['level.program', 'learningMaterials'])
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get();

        $competencies = ReportCompetency::with('level')
            ->when($levelFilter === 'all', fn ($q) => $q->whereNull('program_level_id'))
            ->when(is_numeric($levelFilter), fn ($q) => $q->where('program_level_id', (int) $levelFilter))
            ->orderBy('program_level_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $classMaterials = ClassLearningMaterial::with('class')
            ->when($classFilter, fn ($q) => $q->where('class_id', $classFilter))
            ->orderBy('class_id')
            ->orderBy('sort_order')
            ->get();

        $achievementLevels = AchievementLevel::orderBy('sort_order')->get();

        return view('kurikulum.kompetensi', compact(
            'levels',
            'classes',
            'competencies',
            'classMaterials',
            'achievementLevels',
            'levelFilter',
            'classFilter'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'program_level_id' => ['nullable', 'integer', 'exists:program_levels,id'],
            'name'             => ['required', 'string', 'max:255'],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $levelId = $data['program_level_id'] ?? null;
        $nextOrder = $data['sort_order']
            ?? (ReportCompetency::where('program_level_id', $levelId)->max('sort_order') + 1);

        ReportCompetency::create([
            'program_level_id' => $levelId,
            'name'             => $data['name'],
            'sort_order'       => $nextOrder,
            'is_active'        => true,
        ]);

        return back()->with('success', 'Kompetensi berhasil ditambahkan.');
    }

    public function update(Request $request, ReportCompetency $competency)
    {
        $data = $request->validate([
            'program_level_id' => ['nullable', 'integer', 'exists:program_levels,id'],
            'name'             => ['required', 'string', 'max:255'],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $competency->update([
            'program_level_id' => $data['program_level_id'] ?? null,
            'name'             => $data['name'],
            'sort_order'       => $data['sort_order'] ?? $competency->sort_order,
            'is_active'        => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Kompetensi diperbarui.');
    }

    public function destroy(ReportCompetency $competency)
    {
        $competency->delete();

        return back()->with('success', 'Kompetensi dihapus.');
    }

    /** Simpan template Learning Material per Kelas */
    public function storeClassMaterial(Request $request)
    {
        $data = $request->validate([
            'class_id'      => ['required', 'exists:classes,id'],
            'material_name' => ['required', 'string', 'max:255'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
        ]);

        $nextOrder = $data['sort_order'] ?? (ClassLearningMaterial::where('class_id', $data['class_id'])->max('sort_order') + 1);

        ClassLearningMaterial::create([
            'class_id'      => $data['class_id'],
            'material_name' => $data['material_name'],
            'sort_order'    => $nextOrder,
        ]);

        return back()->with('success', 'Learning Material untuk kelas berhasil ditambahkan.');
    }

    public function destroyClassMaterial(ClassLearningMaterial $material)
    {
        $material->delete();

        return back()->with('success', 'Learning Material kelas dihapus.');
    }

    public function updateDescriptions(Request $request)
    {
        $descriptions = $request->input('descriptions', []);

        foreach ($descriptions as $id => $desc) {
            AchievementLevel::whereKey($id)->update(['description' => $desc]);
        }

        return back()->with('success', 'Deskripsi Achievement Level diperbarui.');
    }
}
