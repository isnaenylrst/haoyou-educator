<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialVocabFactory extends Factory
{
    /**
     * Kumpulan kosakata dikelompokkan supaya seeder bisa ambil BERBEDA-BEDA
     * per materi (tidak ada hanzi yang sama dua kali dalam satu materi).
     */
    public const VOCAB_POOL = [
        ['hanzi' => '你好', 'pinyin' => 'nǐ hǎo', 'meaning' => 'Halo', 'example' => '你好，老师！'],
        ['hanzi' => '谢谢', 'pinyin' => 'xiè xiè', 'meaning' => 'Terima kasih', 'example' => '谢谢你的帮助。'],
        ['hanzi' => '再见', 'pinyin' => 'zài jiàn', 'meaning' => 'Sampai jumpa', 'example' => '明天见，再见！'],
        ['hanzi' => '老师', 'pinyin' => 'lǎo shī', 'meaning' => 'Guru', 'example' => '我的老师很好。'],
        ['hanzi' => '学生', 'pinyin' => 'xué shēng', 'meaning' => 'Murid', 'example' => '我是学生。'],
        ['hanzi' => '朋友', 'pinyin' => 'péng yǒu', 'meaning' => 'Teman', 'example' => '他是我的朋友。'],
        ['hanzi' => '爸爸', 'pinyin' => 'bà ba', 'meaning' => 'Ayah', 'example' => '我爸爸在家。'],
        ['hanzi' => '妈妈', 'pinyin' => 'mā ma', 'meaning' => 'Ibu', 'example' => '妈妈做饭。'],
        ['hanzi' => '喜欢', 'pinyin' => 'xǐ huān', 'meaning' => 'Suka', 'example' => '我喜欢中文。'],
        ['hanzi' => '中文', 'pinyin' => 'zhōng wén', 'meaning' => 'Bahasa Mandarin', 'example' => '我学中文。'],
        ['hanzi' => '今天', 'pinyin' => 'jīn tiān', 'meaning' => 'Hari ini', 'example' => '今天很热。'],
        ['hanzi' => '明天', 'pinyin' => 'míng tiān', 'meaning' => 'Besok', 'example' => '明天有课。'],
    ];

    public function definition(): array
    {
        $vocab = fake()->randomElement(self::VOCAB_POOL);

        return [
            'order_number' => 1,
            'hanzi' => $vocab['hanzi'],
            'pinyin' => $vocab['pinyin'],
            'meaning' => $vocab['meaning'],
            'example_sentence' => $vocab['example'],
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Pakai entri spesifik dari VOCAB_POOL — dipakai seeder supaya
     * satu materi tidak punya kosakata duplikat.
     */
    public function fromPool(array $vocab): static
    {
        return $this->state(fn () => [
            'hanzi' => $vocab['hanzi'],
            'pinyin' => $vocab['pinyin'],
            'meaning' => $vocab['meaning'],
            'example_sentence' => $vocab['example'],
        ]);
    }
}