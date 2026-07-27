<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialVocabSeeder extends Seeder
{
    /**
     * Tiap materi mendapat 5 kosakata contoh (hanzi/pinyin/arti).
     */
    public function run(): void
    {
        $now = now();

        $vocabBank = [
            ['hanzi' => '你好', 'pinyin' => 'nǐ hǎo', 'meaning' => 'Halo'],
            ['hanzi' => '谢谢', 'pinyin' => 'xiè xiè', 'meaning' => 'Terima kasih'],
            ['hanzi' => '再见', 'pinyin' => 'zài jiàn', 'meaning' => 'Sampai jumpa'],
            ['hanzi' => '老师', 'pinyin' => 'lǎo shī', 'meaning' => 'Guru'],
            ['hanzi' => '学生', 'pinyin' => 'xué shēng', 'meaning' => 'Murid'],
            ['hanzi' => '朋友', 'pinyin' => 'péng yǒu', 'meaning' => 'Teman'],
        ];

        $materialIds = DB::table('materials')->pluck('id');

        foreach ($materialIds as $materialId) {
            $selected = collect($vocabBank)->shuffle()->take(5)->values();

            foreach ($selected as $order => $vocab) {
                DB::table('materials_vocab')->insert([
                    'material_id' => $materialId,
                    'order_number' => $order + 1,
                    'hanzi' => $vocab['hanzi'],
                    'pinyin' => $vocab['pinyin'],
                    'meaning' => $vocab['meaning'],
                    'example_sentence' => fake()->optional()->sentence(),
                    'notes' => fake()->optional()->sentence(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}