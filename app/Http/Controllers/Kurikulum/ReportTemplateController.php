<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgressReportTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Kurikulum menyusun Learning Materials per pertemuan untuk tiap program (Feixiang, Maochong, HSK, ...). */
class ReportTemplateController extends Controller
{
    public function index()
    {
        return view('kurikulum.report-templates.index', [
            'templates'  => ProgressReportTemplate::with(['category', 'level'])->withCount('meetings')->orderBy('name')->get(),
            'categories' => ProgramCategory::orderBy('sort_order')->orderBy('category_name')->get(),
            'levels'     => ProgramLevel::with('category')->orderBy('level_name')->get(),
            // template tingkat program (tanpa level khusus) per kategori: dasar tabel "status per program"
            'byCategory' => ProgressReportTemplate::whereNull('program_level_id')->withCount('meetings')->get()->keyBy('program_category_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'program_category_id' => ['nullable', 'integer', 'exists:program_categories,id'],
            'program_level_id'    => ['nullable', 'integer', 'exists:program_levels,id'],
        ], [
            'name.required' => 'Nama template wajib diisi.',
        ]);

        if (empty($data['program_category_id']) && empty($data['program_level_id'])) {
            return back()->withInput()->withErrors(['program_category_id' => 'Pilih program (kategori) atau level untuk template ini.']);
        }

        $exists = ProgressReportTemplate::where('program_category_id', $data['program_category_id'] ?? null)
            ->where('program_level_id', $data['program_level_id'] ?? null)->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['name' => 'Template untuk program/level tersebut sudah ada. Edit template yang sudah ada.']);
        }

        $template = ProgressReportTemplate::create($data + ['updated_by' => Auth::id()]);

        return redirect()->route('kurikulum.report-template.edit', $template)
            ->with('success', 'Template dibuat. Isi Learning Materials tiap pertemuan di bawah.');
    }

    public function edit(ProgressReportTemplate $template)
    {
        $template->load(['meetings', 'category', 'level']);

        return view('kurikulum.report-templates.edit', compact('template'));
    }

    public function update(Request $request, ProgressReportTemplate $template)
    {
        // bersihkan HTML dari editor sebelum divalidasi; materi yang hanya berisi paragraf kosong dianggap kosong
        $request->merge(['meetings' => collect((array) $request->input('meetings', []))->map(function ($m) {
            if (is_array($m)) {
                $m['materials'] = \App\Support\RichText::clean((string) ($m['materials'] ?? ''));
            }

            return $m;
        })->all()]);

        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'meetings'             => ['required', 'array', 'min:1'],
            'meetings.*.number'    => ['required', 'integer', 'min:1', 'max:200', 'distinct'],
            'meetings.*.materials' => ['required', 'string', 'max:20000'],
        ], [
            'meetings.required'             => 'Isi minimal 1 pertemuan.',
            'meetings.*.number.distinct'    => 'Nomor pertemuan tidak boleh sama.',
            'meetings.*.number.required'    => 'Nomor pertemuan wajib diisi.',
            'meetings.*.materials.required' => 'Learning Materials tiap pertemuan wajib diisi.',
        ]);

        DB::transaction(function () use ($template, $data) {
            $template->update(['name' => $data['name'], 'updated_by' => Auth::id()]);
            $template->meetings()->delete();
            foreach ($data['meetings'] as $m) {
                $template->meetings()->create([
                    'meeting_number' => (int) $m['number'],
                    'materials'      => trim($m['materials']),
                ]);
            }
        });

        return back()->with('success', 'Template disimpan. Guru langsung memakainya untuk kelas program ini.');
    }

    public function destroy(ProgressReportTemplate $template)
    {
        $template->delete();

        return redirect()->route('kurikulum.report-template.index')->with('success', 'Template dihapus.');
    }
}