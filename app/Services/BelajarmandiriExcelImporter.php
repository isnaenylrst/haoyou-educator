<?php

namespace App\Services;

use App\Models\GrammarPoint;
use App\Models\GrammarQuestion;
use App\Models\Material;
use App\Models\VocabChapter;
use App\Models\VocabExample;
use App\Models\VocabTrap;
use App\Models\VocabWord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Import template Excel Belajar Mandiri (sheet: Pertemuan, Kosakata, Vocab Traps, Grammar, Soal Grammar)
 * ke vocab_chapters, vocab_words, vocab_examples, vocab_traps, grammar_points, dan grammar_questions.
 *
 * Dipanggil DI DALAM DB::transaction milik controller, jadi kalau ada
 * RuntimeException semua perubahan otomatis dibatalkan.
 */
class BelajarmandiriExcelImporter
{
    private const MAX_ORDER = 65535;                       // vocab_words.order_number (smallint)

    /** Kolom contoh kalimat di tabel vocab_examples (varchar 255; sentence_meaning NOT NULL). */
    private const EXAMPLE_COLUMNS = ['hanzi' => 'sentence_hanzi', 'pinyin' => 'sentence_pinyin', 'meaning' => 'sentence_meaning'];

    private array $summary = [
        'imported' => 0, 'updated' => 0, 'skipped' => 0, 'duplicates' => 0,
        'grammar_new' => 0, 'grammar_updated' => 0, 'traps_new' => 0, 'traps_updated' => 0,
        'questions_new' => 0, 'questions_updated' => 0, 'notes' => [],
    ];

    public static function supports(?string $extension): bool
    {
        return in_array(strtolower((string) $extension), ['xlsx', 'xls'], true);
    }

    public function import(Material $material, string $absolutePath): array
    {
        if (! $material->level_id) {
            throw new \RuntimeException('Pilih Level (mis. HSK 1) dulu, karena Excel Belajar Mandiri harus masuk ke satu level.');
        }

        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadDataOnly(true);
        $book = $reader->load($absolutePath);

        $vocabSheet = $this->sheet($book, 'kosakata');
        if (! $vocabSheet) {
            throw new \RuntimeException('Sheet "Kosakata" tidak ditemukan di file Excel. Gunakan template import Belajar Mandiri.');
        }

        $chapters = $this->importChapters($material, $this->sheet($book, 'pertemuan'));
        $this->importVocab($material, $vocabSheet, $chapters);

        if ($traps = $this->sheet($book, 'vocab traps')) {
            $this->importTraps($material, $traps);
        }

        if ($grammar = $this->sheet($book, 'grammar')) {
            $this->importGrammar($material, $grammar);

            // soal grammar harus diproses SETELAH pola grammar tersimpan
            if ($questions = $this->sheet($book, 'soal grammar')) {
                $this->importQuestions($material, $questions);
            }
        }

        $book->disconnectWorksheets();

        return $this->summary;
    }

    /* ------------------------------------------------------------ PERTEMUAN */

    /** @return array<int,int> nomor pertemuan => id vocab_chapters */
    private function importChapters(Material $m, ?Worksheet $ws): array
    {
        $ids = [];
        if (! $ws) {
            return $ids;
        }

        foreach ($this->rows($ws, ['pertemuan']) as $r) {
            $no = $this->int($r['pertemuan']);
            if (! $no || $no > 255) {
                continue;
            }

            $attrs = $this->only('vocab_chapters', [
                'title'         => $this->str($r['judul'] ?? '') ?: null,
                'worksheet_url' => $this->str($r['link worksheet'] ?? '', 500) ?: null,
                'dialog'        => $this->str($r['dialog'] ?? '', 20000) ?: null,
                'tongue_twister' => $this->str($r['tongue twister'] ?? '', 20000) ?: null,
            ]);

            $chapter = VocabChapter::firstOrNew(['program_level_id' => $m->level_id, 'number' => $no]);
            $chapter->fill($attrs)->save();
            $ids[$no] = $chapter->id;
        }

        return $ids;
    }

    private function chapterId(Material $m, int $no, array &$chapters): int
    {
        if (! isset($chapters[$no])) {
            $chapters[$no] = VocabChapter::firstOrCreate(
                ['program_level_id' => $m->level_id, 'number' => $no],
                $this->only('vocab_chapters', ['title' => 'Bab ' . $no])
            )->id;
        }

        return $chapters[$no];
    }

    /* ------------------------------------------------------------ KOSAKATA */

    private function importVocab(Material $m, Worksheet $ws, array &$chapters): void
    {
        $rows = $this->rows($ws, ['pertemuan', 'hanzi', 'pinyin', 'arti indonesia']);

        // urut: pertemuan lalu urutan, supaya order_number rapi
        uasort($rows, fn ($a, $b) => [$this->int($a['pertemuan']), $this->int($a['urutan'] ?? 0)]
                                  <=> [$this->int($b['pertemuan']), $this->int($b['urutan'] ?? 0)]);

        $order = (int) VocabWord::max('order_number');
        $seen  = [];

        foreach ($rows as $line => $r) {
            $no      = $this->int($r['pertemuan']);
            $hanzi   = $this->str($r['hanzi']);
            $pinyin  = $this->str($r['pinyin']);
            $meaning = $this->str($r['arti indonesia']);
            $en      = $this->str($r['arti english'] ?? '');
            $type    = $this->str($r['jenis kata'] ?? '', 30);

            if ($hanzi === '' && $pinyin === '' && $meaning === '') {
                continue;
            }
            if (! $no || $no > 255 || $hanzi === '' || $pinyin === '' || $meaning === '') {
                $this->skip("Kosakata baris {$line}: Pertemuan, Hanzi, Pinyin, dan Arti Indonesia wajib diisi");
                continue;
            }
            if (mb_strlen($hanzi) > 255 || mb_strlen($pinyin) > 255 || mb_strlen($meaning) > 255 || mb_strlen($en) > 255) {
                $this->skip("Kosakata baris {$line}: ada kolom lebih dari 255 karakter");
                continue;
            }
            if (isset($seen[$hanzi])) {                      // hanzi sama di level yang sama: pakai kemunculan pertama
                $this->summary['duplicates']++;
                continue;
            }
            $seen[$hanzi] = true;

            $attrs = $this->only('vocab_words', [
                'program_level_id' => $m->level_id,
                'material_id'      => $m->id,
                'vocab_chapter_id' => $this->chapterId($m, $no, $chapters),
                'pinyin'           => $pinyin,
                'meaning_id'       => $meaning,
                'meaning_en'       => $en !== '' ? $en : null,
                'word_type'        => $type !== '' ? $type : null,
                'is_extra'         => $this->yes($r['tambahan'] ?? ''),
                'is_tingxie'       => $this->yes($r['tingxie'] ?? ''),
            ]);

            $word = VocabWord::where('program_level_id', $m->level_id)->where('hanzi', $hanzi)->where('trap_only', false)->first();

            if ($word) {
                $word->update($attrs);
                $this->summary['updated']++;
            } else {
                if ($order >= self::MAX_ORDER) {
                    $this->skip("Kosakata baris {$line}: bank kata penuh");
                    continue;
                }
                $word = VocabWord::create($attrs + ['hanzi' => $hanzi, 'order_number' => ++$order]);
                $this->summary['imported']++;
            }

            $this->saveExample($word, $r, true);
        }
    }

    /* ------------------------------------------------------------ VOCAB TRAPS */

    private function importTraps(Material $m, Worksheet $ws): void
    {
        $rows  = $this->rows($ws, ['aktif (isi ya)', 'tipe', 'hanzi', 'pinyin', 'arti indonesia']);
        $order = (int) VocabWord::max('order_number');
        $sort  = ['same_meaning' => 0, 'same_hanzi' => 0];

        foreach ($rows as $line => $r) {
            if (! $this->yes($r['aktif (isi ya)'])) {
                continue;                                       // hanya baris yang ditandai Ya
            }

            $type = match (mb_strtolower($this->str($r['tipe']))) {
                'arti sama', 'same_meaning' => 'same_meaning',
                'hanzi sama', 'same_hanzi'  => 'same_hanzi',
                default                     => null,
            };
            $hanzi   = $this->str($r['hanzi']);
            $pinyin  = $this->str($r['pinyin']);
            $meaning = $this->str($r['arti indonesia']);

            if (! $type || $hanzi === '' || $pinyin === '' || $meaning === '') {
                $this->skip("Vocab Traps baris {$line}: Tipe (Arti Sama / Hanzi Sama), Hanzi, Pinyin, dan Arti Indonesia wajib diisi");
                continue;
            }

            $explanations = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) ($r['penjelasan (satu poin per baris)'] ?? '')))));

            try {
                DB::transaction(function () use ($m, $r, $type, $hanzi, $pinyin, $meaning, $explanations, &$order, &$sort) {
                    $base = VocabWord::where('program_level_id', $m->level_id)->where('hanzi', $hanzi);

                    // kata yang sama persis (hanzi + pinyin + arti) dipakai ulang; beda pinyin/arti = kata baru khusus traps
                    $word = (clone $base)->whereRaw('BINARY pinyin = ?', [$pinyin])
                        ->where('meaning_id', $meaning)->orderBy('trap_only')->first();

                    if (! $word && $type === 'same_meaning') {
                        $word = (clone $base)->orderBy('trap_only')->first();   // pasangan arti sama: cukup cocok hanzi
                    }

                    if (! $word) {
                        if ($order >= self::MAX_ORDER) {
                            throw new \RuntimeException('bank kata penuh');
                        }
                        $word = VocabWord::create($this->only('vocab_words', [
                            'program_level_id' => $m->level_id,
                            'material_id'      => $m->id,
                            'hanzi'            => $hanzi,
                            'pinyin'           => $pinyin,
                            'meaning_id'       => $meaning,
                            'meaning_en'       => $this->str($r['arti english'] ?? '') ?: null,
                            'word_type'        => $this->str($r['jenis kata'] ?? '', 30) ?: null,
                            'order_number'     => ++$order,
                            'trap_only'        => true,
                        ]));
                    }

                    $this->saveExample($word, $r, false);

                    $trap  = VocabTrap::firstOrNew(['vocab_word_id' => $word->id, 'type' => $type]);
                    $isNew = ! $trap->exists;
                    $trap->fill($this->only('vocab_traps', [
                        'group_key'    => $this->str($r['grup'] ?? '') ?: null,
                        'explanations' => $explanations ?: null,
                        'sort_order'   => ++$sort[$type],          // urut sesuai baris di Excel (grup tidak terpisah)
                    ]))->save();

                    $this->summary[$isNew ? 'traps_new' : 'traps_updated']++;
                });
            } catch (\Throwable $e) {
                $this->skip("Vocab Traps baris {$line}: gagal disimpan ({$e->getMessage()})");
            }
        }
    }

    /** Simpan contoh kalimat. $replace=false: jangan timpa kalau kata sudah punya contoh. */
    private function saveExample(VocabWord $word, array $r, bool $replace): void
    {
        $hanzi = $this->str($r['contoh hanzi'] ?? '');
        if ($hanzi === '' || $this->only('vocab_examples', array_fill_keys(self::EXAMPLE_COLUMNS, 1)) === []) {
            return;
        }
        if (! $replace && $word->examples()->exists()) {
            return;
        }

        $word->examples()->delete();
        VocabExample::create($this->only('vocab_examples', [
            'vocab_word_id'                  => $word->id,
            'sort_order'                     => 1,
            self::EXAMPLE_COLUMNS['hanzi']   => $hanzi,
            self::EXAMPLE_COLUMNS['pinyin']  => $this->str($r['contoh pinyin'] ?? '') ?: null,
            self::EXAMPLE_COLUMNS['meaning'] => $this->str($r['contoh arti'] ?? ''),   // kolom NOT NULL: kosong = ''
        ]));
    }

    /* ------------------------------------------------------------ GRAMMAR */

    private function importGrammar(Material $m, Worksheet $ws): void
    {
        $order = 0;

        foreach ($this->rows($ws, ['pertemuan', 'grammar (hanzi)']) as $line => $r) {
            $hanzi = $this->str($r['grammar (hanzi)']);
            if ($hanzi === '') {
                continue;
            }
            $no = $this->int($r['pertemuan']);
            if (! $no || $no > 255 || mb_strlen($hanzi) > 255) {
                $this->skip("Grammar baris {$line}: Pertemuan wajib angka dan Grammar maksimal 255 karakter");
                continue;
            }

            $contoh = [];
            for ($i = 1; $i <= 3; $i++) {
                $h = $this->str($r["contoh {$i}"] ?? '', 500);
                if ($h !== '') {
                    $contoh[] = [$h, $this->str($r["arti {$i}"] ?? '', 500)];
                }
            }

            $point = GrammarPoint::firstOrNew([
                'program_level_id' => $m->level_id, 'chapter_number' => $no, 'hanzi' => $hanzi,
            ]);
            $isNew = ! $point->exists;
            $point->fill([
                'material_id'  => $m->id,
                'order_number' => ++$order,
                'arti'         => $this->str($r['arti grammar'] ?? '') ?: null,
                'pinyin'       => $this->str($r['pinyin'] ?? '') ?: null,
                'fungsi'       => $this->str($r['fungsi'] ?? '', 2000) ?: null,
                'pola'         => $this->str($r['pola'] ?? '', 1000) ?: null,
                'contoh'       => $contoh,
            ])->save();

            $this->summary[$isNew ? 'grammar_new' : 'grammar_updated']++;
        }
    }

    /* ------------------------------------------------------------ SOAL GRAMMAR */

    /**
     * Sheet "Soal Grammar": satu baris = satu soal untuk satu pola.
     * Pola dicocokkan lewat Pertemuan + Grammar (Hanzi) dengan sheet Grammar.
     * Soal yang sama (pola + teks soal) diperbarui, bukan digandakan.
     */
    private function importQuestions(Material $m, Worksheet $ws): void
    {
        if (! Schema::hasTable('grammar_questions')) {
            $this->skip('Soal Grammar: tabel grammar_questions belum ada. Jalankan php artisan migrate.');
            return;
        }

        $rows = $this->rows($ws, ['pertemuan', 'grammar (hanzi)', 'soal (indonesia)']);

        $points = GrammarPoint::where('program_level_id', $m->level_id)->get()
            ->keyBy(fn ($g) => $g->chapter_number . '|' . trim($g->hanzi));

        $seq = 0;

        foreach ($rows as $line => $r) {
            $soal = $this->str($r['soal (indonesia)']);
            if ($soal === '') {
                continue;                                       // baris soal kosong dilewati
            }

            $no    = $this->int($r['pertemuan']);
            $hanzi = $this->str($r['grammar (hanzi)']);
            $point = $points->get($no . '|' . $hanzi);

            if (! $point) {
                $this->skip("Soal Grammar baris {$line}: pola \"{$hanzi}\" di Pertemuan {$no} tidak ada di sheet Grammar");
                continue;
            }

            $options = [];
            $benar   = 0;
            $bad     = false;

            for ($i = 1; $i <= 4; $i++) {
                $text      = $this->str($r["pilihan {$i}"] ?? '');
                $rawStatus = trim((string) ($r["status {$i}"] ?? ''));

                if ($text === '' && $rawStatus === '') {
                    continue;                                   // pilihan opsional yang tidak diisi
                }

                $status = $this->status($rawStatus);
                if ($text === '' || $status === null) {
                    $bad = true;
                    break;
                }

                $options[] = [$text, $status];
                if ($status === 'benar') {
                    $benar++;
                }
            }

            if ($bad || count($options) < 2 || $benar !== 1) {
                $this->skip("Soal Grammar baris {$line}: minimal 2 pilihan, tiap pilihan wajib punya Status (benar/kurang/salah), dan harus ada tepat 1 benar");
                continue;
            }

            $question = GrammarQuestion::firstOrNew(['grammar_point_id' => $point->id, 'soal' => $soal]);
            $isNew    = ! $question->exists;
            $question->fill([
                'program_level_id' => $m->level_id,
                'chapter_number'   => $no,
                'order_number'     => ++$seq,
                'options'          => $options,
            ])->save();

            $this->summary[$isNew ? 'questions_new' : 'questions_updated']++;
        }
    }

    private function status($v): ?string
    {
        return match (mb_strtolower(trim((string) $v))) {
            'benar', 'b'                => 'benar',
            'kurang', 'kurang tepat', 'k' => 'kurang',
            'salah', 's'                => 'salah',
            default                     => null,
        };
    }

    /* ------------------------------------------------------------ HELPER */

    private function sheet($book, string $name): ?Worksheet
    {
        foreach ($book->getWorksheetIterator() as $ws) {
            if (mb_strtolower(trim($ws->getTitle())) === $name) {
                return $ws;
            }
        }

        return null;
    }

    /**
     * Baris -> array berkunci nama kolom (huruf kecil). Baris kosong dibuang.
     * @return array<int,array<string,mixed>> kunci = nomor baris di Excel
     */
    private function rows(Worksheet $ws, array $required): array
    {
        $data = $ws->toArray(null, true, false, false);
        $head = array_map(fn ($h) => mb_strtolower(trim((string) $h)), array_shift($data) ?? []);

        foreach ($required as $col) {
            if (! in_array($col, $head, true)) {
                throw new \RuntimeException("Sheet \"{$ws->getTitle()}\": kolom \"{$col}\" tidak ditemukan. Gunakan template import Belajar Mandiri.");
            }
        }
        if (count($data) > 3000) {
            throw new \RuntimeException("Sheet \"{$ws->getTitle()}\" lebih dari 3000 baris. Pecah menjadi beberapa file.");
        }

        $out = [];
        foreach ($data as $i => $row) {
            if (! array_filter($row, fn ($v) => trim((string) $v) !== '')) {
                continue;
            }
            $out[$i + 2] = array_combine($head, array_slice(array_pad($row, count($head), null), 0, count($head)));
        }

        return $out;
    }

    private function str($v, int $max = 255): string
    {
        return mb_substr(trim(str_replace("\xC2\xA0", ' ', (string) $v)), 0, $max);
    }

    private function int($v): int
    {
        return preg_match('/\d+/', (string) $v, $m) ? (int) $m[0] : 0;
    }

    private function yes($v): bool
    {
        return in_array(mb_strtolower(trim((string) $v)), ['ya', 'y', 'yes', '1', 'x', 'true'], true);
    }

    /** Buang atribut yang kolomnya tidak ada di tabel (aman terhadap perbedaan skema). */
    private function only(string $table, array $attrs): array
    {
        return array_intersect_key($attrs, array_flip(Schema::getColumnListing($table)));
    }

    private function skip(string $msg): void
    {
        $this->summary['skipped']++;
        if (count($this->summary['notes']) < 15) {
            $this->summary['notes'][] = $msg;
        }
    }
}