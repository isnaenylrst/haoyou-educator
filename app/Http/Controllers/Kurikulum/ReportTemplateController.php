<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\PrivatePackage;
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
            'templates'  => ProgressReportTemplate::with(['category', 'level', 'privatePackage'])->withCount('meetings')->orderBy('name')->get(),
            'categories' => ProgramCategory::orderBy('sort_order')->orderBy('category_name')->get(),
            'levels'     => ProgramLevel::with('category')->orderBy('level_name')->get(),
            // paket private sesuai menu Admin -> Program & Level -> Paket Private
            'privatePackages' => PrivatePackage::orderByDesc('is_active')->orderBy('package_name')->get(),
            // template tingkat program (tanpa level khusus) per kategori: dasar tabel "status per program"
            'byCategory' => ProgressReportTemplate::whereNotNull('program_category_id')->whereNull('program_level_id')
                ->withCount('meetings')->get()->keyBy('program_category_id'),
            // template per paket private
            'byPackage'  => ProgressReportTemplate::whereNotNull('private_package_id')
                ->withCount('meetings')->get()->keyBy('private_package_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'program_category_id' => ['nullable', 'integer', 'exists:program_categories,id'],
            'program_level_id'    => ['nullable', 'integer', 'exists:program_levels,id'],
            'private_package_id'  => ['nullable', 'integer', 'exists:private_packages,id'],
        ], [
            'name.required'               => 'Nama template wajib diisi.',
            'private_package_id.exists'   => 'Paket private tidak ditemukan.',
        ]);

        // Template private dikaitkan ke paket private saja (bukan ke kategori / level reguler)
        if (! empty($data['private_package_id'])) {
            $data['program_category_id'] = null;
            $data['program_level_id']    = null;
        }

        if (empty($data['program_category_id']) && empty($data['program_level_id']) && empty($data['private_package_id'])) {
            return back()->withInput()->withErrors(['program_category_id' => 'Pilih program (kategori), level, atau paket private untuk template ini.']);
        }

        $exists = ProgressReportTemplate::where('program_category_id', $data['program_category_id'] ?? null)
            ->where('program_level_id', $data['program_level_id'] ?? null)
            ->where('private_package_id', $data['private_package_id'] ?? null)
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['name' => 'Template untuk program / level / paket private tersebut sudah ada. Edit template yang sudah ada.']);
        }

        $template = ProgressReportTemplate::create($data + ['updated_by' => Auth::id()]);

        return redirect()->route('kurikulum.report-template.edit', $template)
            ->with('success', 'Template dibuat. Isi Learning Materials tiap pertemuan di bawah.');
    }

    public function edit(ProgressReportTemplate $template)
    {
        $template->load(['meetings', 'category', 'level', 'privatePackage']);

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