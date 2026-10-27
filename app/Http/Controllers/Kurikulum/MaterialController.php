<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialVocab;
use App\Models\ProgramLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    private const MAX_VOCAB = 255; // kolom order_number bertipe tinyint

    private const MESSAGES = [
        'meeting_number.unique'  => 'Pertemuan ini sudah ada pada level yang sama.',
        'meeting_number.max'     => 'Nomor pertemuan maksimal 255.',
        'material_file.required' => 'File materi wajib diunggah.',
        'material_file.mimes'    => 'File harus berformat PDF, DOC, DOCX, PPT, atau PPTX.',
        'material_file.max'      => 'Ukuran file maksimal 10 MB.',
        'material_file.uploaded' => 'Upload gagal. Periksa upload_max_filesize dan post_max_size di php.ini.',
    ];

    private const ATTRIBUTES = [
        'level_id'       => 'level',
        'meeting_number' => 'pertemuan',
        'title'          => 'judul materi',
        'syllabus'       => 'silabus',
        'cco'            => 'CCO',
        'lesson_plan'    => 'lesson plan',
        'activity'       => 'aktivitas',
        'box_code'       => 'kode box',
        'equipment_list' => 'perlengkapan',
        'material_file'  => 'file materi',
        'vocabularies'   => 'kosakata',
    ];

    // ------------------------------------------------------------------ LIST

    public function index(Request $request)
    {
        $request->validate([
            'level_id' => ['nullable', 'integer'],
            'q'        => ['nullable', 'string', 'max:100'],
        ]);

        $materials = Material::with(['level.program', 'uploader', 'vocabularies'])
            ->withCount(['teacherMaterials', 'teachingJournals'])
            ->when($request->filled('level_id'), fn ($q) => $q->where('level_id', $request->level_id))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim($request->q);

                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', "%{$term}%")
                        ->orWhere('syllabus', 'like', "%{$term}%")
                        ->orWhere('box_code', 'like', "%{$term}%");

                    if (ctype_digit($term)) {
                        $w->orWhere('meeting_number', (int) $term);
                    }
                });
            })
            ->orderBy('level_id')
            ->orderBy('meeting_number')
            ->paginate(10)
            ->withQueryString();

        $levels = ProgramLevel::with(['program', 'category'])->orderBy('level_name')->get();

        return view('kurikulum.materi', compact('materials', 'levels'));
    }

    // ------------------------------------------------------------ VALIDASI

    private function rules(Request $request, ?Material $material = null): array
    {
        return [
            'level_id' => ['required', 'exists:program_levels,id'],
            'meeting_number' => [
                'required', 'integer', 'min:1', 'max:255',
                Rule::unique('materials', 'meeting_number')
                    ->where(fn ($q) => $q->where('level_id', $request->level_id))
                    ->ignore($material?->id),
            ],
            'title'          => ['required', 'string', 'max:255'],
            'syllabus'       => ['nullable', 'string'],
            'cco'            => ['nullable', 'string'],
            'lesson_plan'    => ['nullable', 'string'],
            'activity'       => ['nullable', 'string'],
            'box_code'       => ['nullable', 'string', 'max:50'],
            'equipment_list' => ['nullable', 'string'],
            'material_file'  => [$material ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:10240'],
            'vocabularies'   => ['nullable', 'string'],
        ];
    }

    private function payload(Request $request): array
    {
        return [
            'level_id'       => $request->level_id,
            'meeting_number' => $request->meeting_number,
            'title'          => trim($request->title),
            'syllabus'       => $request->syllabus,
            'cco'            => $request->cco,
            'lesson_plan'    => $request->lesson_plan,
            'activity'       => $request->activity,
            'box_code'       => $request->box_code ? strtoupper(trim($request->box_code)) : null,
            'equipment_list' => $request->equipment_list,
        ];
    }

    // ------------------------------------------------------------ SIMPAN

    public function store(Request $request)
    {
        $request->validate($this->rules($request), self::MESSAGES, self::ATTRIBUTES);

        $path = $request->file('material_file')->store('materials', 'public');
        $skipped = 0;
        $material = null;

        try {
            DB::transaction(function () use ($request, $path, &$skipped, &$material) {
                $material = Material::create($this->payload($request) + [
                    'uploaded_by'        => Auth::id(),
                    'material_file_path' => $path,
                ]);
                $skipped = $this->saveVocabularies($material, $request->vocabularies);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);   // jangan sisakan file yatim
            report($e);

            return back()->withInput()->with('error', 'Gagal menambahkan materi. Silakan coba lagi.');
        }

        if ($material === null) {
            return back()->withInput()->with('error', 'Gagal menambahkan materi. Silakan coba lagi.');
        }

        return $this->done('Materi berhasil ditambahkan.', $skipped, $material->level_id);
    }

    public function update(Request $request, Material $material)
    {
        $request->validate($this->rules($request, $material), self::MESSAGES, self::ATTRIBUTES);

        $oldPath = $material->material_file_path;
        $newPath = $request->hasFile('material_file')
            ? $request->file('material_file')->store('materials', 'public')
            : null;
        $skipped = 0;

        try {
            DB::transaction(function () use ($request, $material, $newPath, &$skipped) {
                $data = $this->payload($request);
                if ($newPath) {
                    $data['material_file_path'] = $newPath;
                }

                $material->update($data);
                $material->vocabularies()->delete();
                $skipped = $this->saveVocabularies($material, $request->vocabularies);
            });
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            report($e);

            return back()->withInput()->with('error', 'Gagal memperbarui materi. Silakan coba lagi.');
        }

        // file lama baru dihapus SETELAH database sukses
        if ($newPath && $oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $this->done('Materi berhasil diperbarui.', $skipped, $material->level_id);
    }

    public function destroy(Material $material)
    {
        if ($material->teachingJournals()->exists() || $material->teacherMaterials()->exists()) {
            return back()->with('error', 'Materi sudah dipakai di jurnal atau pengajuan guru, jadi tidak bisa dihapus.');
        }

        $path = $material->material_file_path;

        try {
            DB::transaction(function () use ($material) {
                $material->vocabularies()->delete();
                $material->delete();
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal menghapus materi. Silakan coba lagi.');
        }

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('kurikulum.materi')->with('success', 'Materi berhasil dihapus.');
    }

    // ------------------------------------------------------------ FILE

    public function download(Material $material)
    {
        abort_unless($material->file_exists, 404, 'File materi tidak ditemukan.');

        $name = (Str::slug($material->title) ?: 'materi-' . $material->id)
            . '.' . $material->file_extension;

        return response()->download(storage_path('app/public/' . $material->material_file_path), $name);
    }

    /** PDF dibuka di browser; Word/PowerPoint otomatis diunduh. */
    public function preview(Material $material)
    {
        abort_unless($material->file_exists, 404, 'File materi tidak ditemukan.');

        if (!$material->is_pdf) {
            return $this->download($material);
        }

        return response()->file(
            storage_path('app/public/' . $material->material_file_path),
            ['Content-Type' => 'application/pdf']
        );
    }

    // ------------------------------------------------------------ HELPER

    private function done(string $message, int $skipped, int $levelId)
    {
        $redirect = redirect()
            ->route('kurikulum.materi', ['level_id' => $levelId])
            ->with('success', $message);

        return $skipped > 0
            ? $redirect->with('warning', "{$skipped} baris kosakata dilewati karena formatnya salah, kosong, terlalu panjang, atau melebihi " . self::MAX_VOCAB . " baris (format: Hanzi | Pinyin | Arti).")
            : $redirect;
    }

    /** Mengembalikan jumlah baris yang dilewati. */
    private function saveVocabularies(Material $material, ?string $vocabularies): int
    {
        if (!$vocabularies) {
            return 0;
        }

        $order = 1;
        $skipped = 0;

        foreach (preg_split('/\r\n|\r|\n/', $vocabularies) as $row) {
            $row = trim($row);
            if ($row === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $row));
            $core  = array_slice($parts, 0, 3);

            $invalid = count($parts) < 3
                || in_array('', $core, true)
                || collect($core)->contains(fn ($p) => mb_strlen($p) > 255)
                || $order > self::MAX_VOCAB;

            if ($invalid) {
                $skipped++;
                continue;
            }

            MaterialVocab::create([
                'material_id'  => $material->id,
                'order_number' => $order++,
                'hanzi'        => $parts[0],
                'pinyin'       => $parts[1],
                'meaning'      => $parts[2],
            ]);
        }

        return $skipped;
    }
}