<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialVocab;
use App\Models\ProgramLevel;
use App\Models\VocabWord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    private const MAX_VOCAB        = 255;     // materials_vocab.order_number (tinyint)
    private const MAX_HUAHUA_ORDER = 65535;   // vocab_words.order_number (smallint)

    private const MESSAGES = [
        'audience.required'      => 'Pilih tujuan materi: Guru, Siswa, atau Huahua.',
        'meeting_number.unique'  => 'Pertemuan ini sudah ada pada level dan tujuan yang sama.',
        'meeting_number.max'     => 'Nomor pertemuan maksimal 255.',
        'material_file.required' => 'File materi wajib diunggah.',
        'material_file.mimes'    => 'File harus berformat PDF, DOC, DOCX, PPT, atau PPTX.',
        'material_file.max'      => 'Ukuran file maksimal 10 MB.',
        'material_file.uploaded' => 'Upload gagal. Periksa upload_max_filesize dan post_max_size di php.ini.',
    ];

    private const ATTRIBUTES = [
        'audience'       => 'tujuan materi',
        'level_id'       => 'level',
        'meeting_number' => 'pertemuan',
        'title'          => 'judul materi',
        'syllabus'       => 'silabus',
        'description'    => 'deskripsi',
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
            'audience' => ['nullable', Rule::in(Material::AUDIENCES)],
            'level_id' => ['nullable', 'integer'],
            'q'        => ['nullable', 'string', 'max:100'],
        ]);

        $audience = $request->query('audience');

        $materials = Material::with(['level.program', 'uploader', 'vocabularies', 'huahuaWords'])
            ->withCount(['teacherMaterials', 'teachingJournals', 'huahuaWords'])
            ->when($audience, fn ($q) => $q->where('audience', $audience))
            ->when($request->filled('level_id'), fn ($q) => $q->where('level_id', $request->level_id))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim($request->q);

                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', "%{$term}%")
                        ->orWhere('syllabus', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('box_code', 'like', "%{$term}%");

                    if (ctype_digit($term)) {
                        $w->orWhere('meeting_number', (int) $term);
                    }
                });
            })
            ->orderBy('level_id')
            ->orderBy('meeting_number')
            ->paginate(10)
            ->appends($request->query());

        $counts = Material::selectRaw('audience, COUNT(*) as total')
            ->groupBy('audience')
            ->pluck('total', 'audience');

        $levels = ProgramLevel::with(['program', 'category'])->orderBy('level_name')->get();

        return view('kurikulum.materi', compact('materials', 'levels', 'audience', 'counts'));
    }

    // ------------------------------------------------------------ VALIDASI

    private function rules(Request $request, ?Material $material = null): array
    {
        // Tujuan hanya dipilih saat membuat; saat mengubah, tujuan tetap
        $audience = $material?->audience ?? $request->input('audience');

        return [
            'audience' => $material ? ['nullable'] : ['required', Rule::in(Material::AUDIENCES)],
            'level_id' => ['required', 'exists:program_levels,id'],
            'meeting_number' => [
                'required', 'integer', 'min:1', 'max:255',
                Rule::unique('materials', 'meeting_number')
                    ->where(fn ($q) => $q->where('level_id', $request->level_id)->where('audience', $audience))
                    ->ignore($material?->id),
            ],
            'title'          => ['required', 'string', 'max:255'],
            'syllabus'       => ['nullable', 'string'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'cco'            => ['nullable', 'string'],
            'lesson_plan'    => ['nullable', 'string'],
            'activity'       => ['nullable', 'string'],
            'box_code'       => ['nullable', 'string', 'max:50'],
            'equipment_list' => ['nullable', 'string'],
            'material_file'  => [$material ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:10240'],
            'vocabularies'   => ['nullable', 'string', 'max:100000'],
        ];
    }

    /** Kolom khusus Guru dikosongkan untuk materi Siswa/Huahua (dan sebaliknya). */
    private function payload(Request $request, string $audience): array
    {
        $isGuru = $audience === 'Guru';

        return [
            'level_id'       => $request->level_id,
            'meeting_number' => $request->meeting_number,
            'title'          => trim($request->title),
            'syllabus'       => $isGuru ? $request->syllabus : null,
            'cco'            => $isGuru ? $request->cco : null,
            'lesson_plan'    => $isGuru ? $request->lesson_plan : null,
            'activity'       => $isGuru ? $request->activity : null,
            'box_code'       => $isGuru && $request->box_code ? strtoupper(trim($request->box_code)) : null,
            'equipment_list' => $isGuru ? $request->equipment_list : null,
            'description'    => $isGuru ? null : $request->description,
        ];
    }

    // ------------------------------------------------------------ SIMPAN

    public function store(Request $request)
    {
        $request->validate($this->rules($request), self::MESSAGES, self::ATTRIBUTES);

        $audience = $request->audience;
        $path     = $request->file('material_file')->store('materials', 'public');
        $summary  = ['skipped' => 0, 'imported' => 0, 'updated' => 0];

        try {
            /** @var Material $material */
            $material = DB::transaction(function () use ($request, $audience, $path, &$summary) {
                $created = Material::create($this->payload($request, $audience) + [
                    'audience'           => $audience,
                    'uploaded_by'        => Auth::id(),
                    'material_file_path' => $path,
                ]);

                $this->saveVocabulary($created, $request->vocabularies, $summary);

                return $created;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);   // jangan sisakan file yatim
            report($e);

            return back()->withInput()->with('error', 'Gagal menambahkan materi. Silakan coba lagi.');
        }

        return $this->done('Materi berhasil ditambahkan untuk ' . $audience . '.', $summary, $material);
    }

    public function update(Request $request, Material $material)
    {
        $request->validate($this->rules($request, $material), self::MESSAGES, self::ATTRIBUTES);

        $oldPath = $material->material_file_path;
        $newPath = $request->hasFile('material_file')
            ? $request->file('material_file')->store('materials', 'public')
            : null;
        $summary = ['skipped' => 0, 'imported' => 0, 'updated' => 0];

        try {
            DB::transaction(function () use ($request, $material, $newPath, &$summary) {
                $data = $this->payload($request, $material->audience);
                if ($newPath) {
                    $data['material_file_path'] = $newPath;
                }

                $material->update($data);

                if ($material->audience !== 'Huahua') {
                    $material->vocabularies()->delete();   // diganti dengan daftar baru
                }
                $this->saveVocabulary($material, $request->vocabularies, $summary);
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

        return $this->done('Materi berhasil diperbarui.', $summary, $material);
    }

    public function destroy(Material $material)
    {
        if ($material->teachingJournals()->exists() || $material->teacherMaterials()->exists()) {
            return back()->with('error', 'Materi sudah dipakai di jurnal atau pengajuan guru, jadi tidak bisa dihapus.');
        }

        $path = $material->material_file_path;

        try {
            DB::transaction(function () use ($material) {
                // Kata di bank Huahua TIDAK dihapus (material_id menjadi NULL) agar progres belajar siswa aman
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

    private function done(string $message, array $summary, Material $material)
    {
        $redirect = redirect()
            ->route('kurikulum.materi', ['audience' => $material->audience, 'level_id' => $material->level_id])
            ->with('success', $message);

        if ($material->audience === 'Huahua' && ($summary['imported'] + $summary['updated']) > 0) {
            $redirect->with('info', "Bank Huahua: {$summary['imported']} kata baru, {$summary['updated']} kata diperbarui.");
        }

        if ($summary['skipped'] > 0) {
            $format = $material->audience === 'Huahua'
                ? 'Hanzi | Pinyin | Arti Indonesia | English (opsional) | Jenis kata (opsional)'
                : 'Hanzi | Pinyin | Arti';

            $redirect->with('warning', "{$summary['skipped']} baris kosakata dilewati karena formatnya salah, kosong, terlalu panjang, atau melebihi batas (format: {$format}).");
        }

        return $redirect;
    }

    /** Guru & Siswa -> materials_vocab; Huahua -> bank vocab_words. */
    private function saveVocabulary(Material $material, ?string $text, array &$summary): void
    {
        if (!$text) {
            return;
        }

        $material->audience === 'Huahua'
            ? $this->importHuahuaWords($material, $text, $summary)
            : $this->saveMaterialVocab($material, $text, $summary);
    }

    private function saveMaterialVocab(Material $material, string $text, array &$summary): void
    {
        $order = 1;

        foreach (preg_split('/\r\n|\r|\n/', $text) as $row) {
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
                $summary['skipped']++;
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
    }

    /**
     * Masukkan kata ke bank Huahua. Kata yang sama (level + hanzi) DIPERBARUI,
     * bukan digandakan. Kata yang dihapus dari daftar TIDAK dihapus dari bank.
     */
    private function importHuahuaWords(Material $material, string $text, array &$summary): void
    {
        $order = (int) VocabWord::max('order_number');

        foreach (preg_split('/\r\n|\r|\n/', $text) as $row) {
            $row = trim($row);
            if ($row === '') {
                continue;
            }

            [$hanzi, $pinyin, $meaningId, $meaningEn, $type] = array_pad(array_map('trim', explode('|', $row)), 5, '');

            $invalid = $hanzi === '' || $pinyin === '' || $meaningId === ''
                || mb_strlen($hanzi) > 255 || mb_strlen($pinyin) > 255
                || mb_strlen($meaningId) > 255 || mb_strlen($meaningEn) > 255
                || mb_strlen($type) > 30;

            if ($invalid) {
                $summary['skipped']++;
                continue;
            }

            $attrs = [
                'program_level_id' => $material->level_id,
                'material_id'      => $material->id,
                'pinyin'           => $pinyin,
                'meaning_id'       => $meaningId,
                'meaning_en'       => $meaningEn !== '' ? $meaningEn : null,
                'word_type'        => $type !== '' ? $type : null,
            ];

            $word = VocabWord::where('program_level_id', $material->level_id)->where('hanzi', $hanzi)->first();

            if ($word) {
                $word->update($attrs);
                $summary['updated']++;
                continue;
            }

            if ($order >= self::MAX_HUAHUA_ORDER) {
                $summary['skipped']++;
                continue;
            }

            VocabWord::create($attrs + ['hanzi' => $hanzi, 'order_number' => ++$order]);
            $summary['imported']++;
        }
    }
}