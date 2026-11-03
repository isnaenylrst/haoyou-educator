<?php

namespace Database\Seeders;

use App\Models\ProgramLevel;
use App\Models\VocabChapter;
use App\Models\VocabWord;
use Illuminate\Database\Seeder;

/**
 * Jalankan: php artisan db:seed --class=HskVocabSeeder
 * Aman dijalankan berulang (updateOrCreate).
 *
 * Untuk menambah bab baru: tambahkan di array dalam data() sesuai urutan
 * (nomor bab => daftar kata). Untuk level lain tambahkan kunci 'HSK 1', 'HSK 2', dst.
 */
class HskVocabSeeder extends Seeder
{
    /** Nama level di tabel program_levels (kolom `name`) — sesuaikan jika berbeda */
    private const LEVEL_COLUMN = 'level_name';

    public function run(): void
    {
        foreach ($this->data() as $levelName => $babs) {
            $level = ProgramLevel::where(self::LEVEL_COLUMN, $levelName)->orderBy('id')->first(); // kata disimpan sekali; level kembar (mis. HSK 3 id 51 & 57) dibaca lewat scopeForLevel
            if (! $level) {
                $this->command?->warn("Level '{$levelName}' tidak ditemukan di program_levels, dilewati.");
                continue;
            }

            $order = 0; // nomor urut berlanjut antar bab dalam satu level
            foreach ($babs as $no => $words) {
                $chapter = VocabChapter::updateOrCreate(
                    ['program_level_id' => $level->id, 'number' => $no],
                    ['title' => "Bab {$no}"]
                );

                foreach ($words as [$hanzi, $pinyin, $type, $id, $en]) {
                    VocabWord::updateOrCreate(
                        ['program_level_id' => $level->id, 'hanzi' => $hanzi, 'pinyin' => $pinyin, 'meaning_id' => $id],
                        [
                            'vocab_chapter_id' => $chapter->id,
                            'order_number'     => ++$order,
                            'meaning_en'       => $en,
                            'word_type'        => $type,
                            'trap_only'        => false,
                        ]
                    );
                }
            }
            $this->command?->info("{$levelName}: {$order} kata di-seed.");
        }
    }

    /** [hanzi, pinyin, jenis kata, arti Indonesia, arti English] */
    private function data(): array
    {
        return [
            'HSK 3' => [
                1 => [
                    ['以为', 'yǐ wéi', '动', 'Pikir', 'Think; consider'],
                    ['像', 'xiàng', '动', 'Seperti', 'Be like'],
                    ['长', 'zhǎng', '动', 'Tumbuh', 'Grow'],
                    ['身高', 'shēn gāo', '名', 'Tinggi (badan)', 'Height'],
                    ['米', 'mǐ', '量', 'Meter', 'Meter (unit)'],
                    ['瘦', 'shòu', '形', 'Kurus', 'Thin'],
                    ['接', 'jiē', '动', 'Bertemu; Menjemput', 'Meet; Pick up'],
                    ['行李', 'xíng li', '名', 'Koper', 'Luggage'],
                    ['丢', 'diū', '动', 'Hilang', 'Lose'],
                    ['箱子', 'xiāng zi', '名', 'Kotak; Bagasi', 'Case; Trunk'],
                    ['号码', 'hào mǎ', '名', 'Nomor', 'Number'],
                    ['好像', 'hǎo xiàng', '副', 'Sepertinya', 'Seemingly'],
                    ['重要', 'zhòng yào', '形', 'Penting', 'Important'],
                    ['着急', 'zháo jí', '形', 'Khawatir', 'Worried'],
                    ['护照', 'hù zhào', '名', 'Paspor', 'Passport'],
                    ['服务台', 'fú wù tái', '名', 'Meja pelayanan', 'Service desk'],
                    ['应该', 'yīng gāi', '助动', 'Harus', 'Should'],
                    ['站', 'zhàn', '动', 'Berdiri', 'Stand'],
                    ['中间', 'zhōng jiān', '名', 'Bagian tengah', 'Middle'],
                    ['短', 'duǎn', '形', 'Pendek', 'Short'],
                    ['头发', 'tóu fa', '名', 'Rambut', 'Hair'],
                    ['年轻', 'nián qīng', '形', 'Muda', 'Young'],
                    ['发现', 'fā xiàn', '动', 'Menemukan', 'Discover; Find'],
                    ['不见', 'bú jiàn', '动', 'Tidak ketemu', 'Be lost'],
                    ['带', 'dài', '动', 'Membawa', 'Bring; Take; Have'],
                    ['帮助', 'bāng zhù', '动', 'Pertolongan', 'Help'],
                    ['照片', 'zhào piàn', '名', 'Foto', 'Photo'],
                ],
                2 => [
                    ['菜单', 'cài dān', '名', 'Menu', 'Menu'],
                    ['又', 'yòu', '副', '...dan...', 'And'],
                    ['饿', 'è', '形', 'Lapar', 'Hungry'],
                    ['渴', 'kě', '形', 'Haus', 'Thirsty'],
                    ['客气', 'kè qi', '动、形', 'Sopan', 'Be polite'],
                    ['饮料', 'yǐn liào', '名', 'Minuman', 'Beverage; drink'],
                    ['好久', 'hǎo jiǔ', '形', 'Sudah lama...', 'For a long time'],
                    ['服务', 'fú wù', '动', 'Melayani; pelayanan', 'Serve'],
                    ['员', 'yuán', '后缀', 'Akhiran setelah nomina/verba untuk menyebut orang (mis. 服务员)', 'Suffix added after noun/verb to indicate a person'],
                    ['双', 'shuāng', '量', 'Pasang (satuan)', 'Pair'],
                    ['筷子', 'kuài zi', '名', 'Sumpit', 'Chopsticks'],
                    ['勺子', 'sháo zi', '名', 'Sendok', 'Spoon'],
                    ['碗', 'wǎn', '名', 'Mangkok', 'Bowl'],
                    ['马上', 'mǎ shàng', '副', 'Langsung', 'Immediately'],
                    ['热情', 'rè qíng', '形', 'Hangat (ramah)', 'Warm'],
                    ['尝', 'cháng', '动', 'Mencicipi', 'Taste'],
                    ['记', 'jì', '动', 'Mengingat', 'Remember'],
                    ['用', 'yòng', '动', 'Pakai', 'Use'],
                    ['鸡', 'jī', '名', 'Ayam', 'Chicken'],
                    ['张', 'zhāng', '量', 'Satuan untuk benda pipih (kertas, lukisan, dll.)', 'A unit for paper, painting, etc.'],
                    ['不用', 'bú yòng', '副', 'Tidak perlu', 'Need not'],
                    ['选', 'xuǎn', '动', 'Memilih', 'Choose'],
                    ['外卖', 'wài mài', '名、动', 'Takeout / Menawarkan layanan antar makanan', 'Takeout / Offer a takeout service'],
                    ['方便', 'fāng biàn', '形', 'Nyaman', 'Convenient'],
                ],
            ],
        ];
    }
}