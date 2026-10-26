<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AchievementLevel;
use App\Models\ProgramLevel;
use App\Models\ReportCompetency;
use Illuminate\Http\Request;

class KompetensiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Kompetensi Progress Report
    |--------------------------------------------------------------------------
    | Kurikulum menetapkan kompetensi apa saja yang dinilai pada progress
    | report, per level (atau semua level). Guru hanya mengisi nilai:
    | Achievement Level + nilai latihan. Deskripsi tiap Achievement Level
    | bersifat baku dan diatur di halaman ini.
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $levelFilter = $request->get('level');

        $levels = ProgramLevel::with(['program', 'category'])
            ->orderBy('level_name')
            ->get();

        $competencies = ReportCompetency::with('level')
            ->when($levelFilter === 'all', fn ($q) => $q->whereNull('program_level_id'))
            ->when(is_numeric($levelFilter), fn ($q) => $q->where('program_level_id', (int) $levelFilter))
            ->orderBy('program_level_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $achievementLevels = AchievementLevel::orderBy('sort_order')->get();

        return view('kurikulum.kompetensi', compact(
            'levels',
            'competencies',
            'achievementLevels',
            'levelFilter'
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

        return back()->with('success', 'Kompetensi ditambahkan.');
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
            'is_active'        => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Kompetensi diperbarui.');
    }

    public function destroy(ReportCompetency $competency)
    {
        // Report lama menyimpan salinan nama kompetensi di isinya,
        // jadi menghapus kompetensi tidak merusak report yang sudah dibuat.
        $competency->delete();

        return back()->with('success', 'Kompetensi dihapus.');
    }

    public function updateDescriptions(Request $request)
    {
        $data = $request->validate([
            'descriptions'   => ['required', 'array'],
            'descriptions.*' => ['required', 'string', 'max:1000'],
        ]);

        foreach (AchievementLevel::all() as $level) {
            if (isset($data['descriptions'][$level->id])) {
                $level->update(['description' => $data['descriptions'][$level->id]]);
            }
        }

        return back()->with('success', 'Deskripsi Achievement Level diperbarui.');
    }
}