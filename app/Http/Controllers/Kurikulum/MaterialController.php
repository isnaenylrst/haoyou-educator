<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\Material;
use App\Models\MaterialVocab;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\User;
use App\Models\VocabWord;
use App\Notifications\AppNotification;
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
            'audience'   => ['nullable', Rule::in(Material::AUDIENCES)],
            'program_id' => ['nullable', 'string'],
            'level_id'   => ['nullable', 'integer'],
            'q'          => ['nullable', 'string', 'max:100'],
        ]);

        $audience = $request->query('audience');
        $selectedProgram = $request->query('program_id');

        $materials = Material::with(['program', 'level.program', 'level.category', 'uploader', 'vocabularies', 'huahuaWords'])
            ->withCount(['teacherMaterials', 'teachingJournals', 'huahuaWords'])
            ->when($audience, fn ($q) => $q->where('audience', $audience))
            ->when($selectedProgram !== null && $selectedProgram !== '', function ($q) use ($selectedProgram) {
                if ($selectedProgram === 'umum') {
                    $q->whereNull('program_id');
                } else {
                    $q->where('program_id', $selectedProgram);
                }
            })
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
            ->orderByRaw('program_id IS NULL DESC, program_id ASC')
            ->orderBy('level_id')
            ->orderBy('meeting_number')
            ->paginate(10)
            ->appends($request->query());

        $counts = Material::selectRaw('audience, COUNT(*) as total')
            ->groupBy('audience')
            ->pluck('total', 'audience');

        $programs = ProgramCategory::with('program')->orderBy('category_name')->get();
        $levels = ProgramLevel::with(['program', 'category'])->orderBy('level_name')->get();

        return view('kurikulum.materi', compact('materials', 'programs', 'levels', 'audience', 'counts', 'selectedProgram'));
    }

    /** Kolom khusus Guru dikosongkan untuk materi Siswa/Huahua (dan sebaliknya). */
    private function payload(Request $request, string $audience): array
    {
        $isGuru = $audience === 'Guru';

        return [
            'program_id'     => $request->program_id ?: null,
            'level_id'       => $request->level_id ?: null,
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

    public function store(StoreMaterialRequest $request)
    {
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

        $this->sendNewMaterialNotification($material);

        return $this->done('Materi berhasil ditambahkan untuk ' . $audience . '.', $summary, $material);
    }

    public function update(UpdateMaterialRequest $request, Material $material)
    {
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

    protected function sendNewMaterialNotification(Material $material): void
    {
        try {
            $material->loadMissing(['program', 'level']);
            $programName = $material->program?->category_name ?? 'Umum';
            $levelLabel  = $material->level ? ' - Level ' . $material->level->level_name : '';
            $fullLabel   = $programName . $levelLabel;

            // 1. Target Guru: jika audience = Guru, kirim ke SEMUA guru aktif. Jika audience = Siswa, kirim ke guru kelas terkait.
            $guruUserIds = collect();
            if ($material->audience === 'Guru') {
                $guruUserIds = User::whereHas('teacher', function ($tq) {
                    $tq->where('status', 'Active');
                })->pluck('id');
            } elseif ($material->audience === 'Siswa') {
                $guruQuery = User::whereHas('teacher', fn ($tq) => $tq->where('status', 'Active'));
                if ($material->level_id) {
                    $guruQuery->whereHas('teacher.classes', function ($q) use ($material) {
                        $q->where('level_id', $material->level_id);
                    });
                } elseif ($material->program_id) {
                    $guruQuery->whereHas('teacher.classes', function ($q) use ($material) {
                        $q->where(function ($sub) use ($material) {
                            $sub->whereHas('level', fn ($l) => $l->where('category_id', $material->program_id))
                                ->orWhereHas('programPackage', fn ($p) => $p->where('category_id', $material->program_id));
                        });
                    });
                }
                $guruUserIds = $guruQuery->pluck('id');
            }

            // 2. Target Siswa: yang ada di program & level ini (dari enrollment aktif), atau semua siswa jika umum
            $siswaUserIds = collect();
            if ($material->audience === 'Siswa' || $material->audience === 'Huahua') {
                $siswaQuery = User::whereHas('student');
                if ($material->level_id) {
                    $siswaQuery->whereHas('student.enrollments', function ($eq) use ($material) {
                        $eq->where('status', 'Active')
                            ->where(function ($cq) use ($material) {
                                $cq->whereHas('class', fn ($c) => $c->where('level_id', $material->level_id))
                                    ->orWhereHas('programPackage', fn ($p) => $p->where('level_id', $material->level_id));
                            });
                    });
                } elseif ($material->program_id) {
                    $siswaQuery->whereHas('student.enrollments', function ($eq) use ($material) {
                        $eq->where('status', 'Active')
                            ->where(function ($cq) use ($material) {
                                $cq->whereHas('class.level', fn ($l) => $l->where('category_id', $material->program_id))
                                    ->orWhereHas('class.programPackage', fn ($p) => $p->where('category_id', $material->program_id))
                                    ->orWhereHas('programPackage', fn ($p) => $p->where('category_id', $material->program_id));
                            });
                    });
                }
                $siswaUserIds = $siswaQuery->pluck('id');
            }

            // Kirim notifikasi ke Guru
            foreach (User::whereIn('id', $guruUserIds)->get() as $guru) {
                $guru->notify(new AppNotification(
                    title: 'Materi Baru: ' . $material->title,
                    message: 'Materi baru "' . $material->title . '" (' . $fullLabel . ') telah diunggah oleh Kurikulum.',
                    url: url('/guru/materi'),
                    type: 'materi',
                    icon: 'fa-book-open',
                    actionLabel: 'Buka Materi'
                ));
            }

            // Kirim notifikasi ke Siswa
            foreach (User::whereIn('id', $siswaUserIds)->get() as $siswa) {
                $targetUrl = $material->audience === 'Huahua' ? url('/student/belajar-vocab/materi') : url('/program-belajar');
                $siswa->notify(new AppNotification(
                    title: 'Materi Belajar Baru: ' . $material->title,
                    message: 'Materi baru "' . $material->title . '" (' . $fullLabel . ') telah diunggah.',
                    url: $targetUrl,
                    type: 'materi',
                    icon: 'fa-book-reader',
                    actionLabel: 'Buka Materi'
                ));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function done(string $message, array $summary, Material $material)
    {
        $redirect = redirect()
            ->route('kurikulum.materi', array_filter([
                'audience'   => $material->audience,
                'program_id' => $material->program_id,
                'level_id'   => $material->level_id,
            ]))
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