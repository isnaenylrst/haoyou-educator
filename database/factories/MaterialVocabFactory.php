<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialVocabFactory extends Factory
{
    public function definition(): array
    {
        $vocab = fake()->randomElement([
            ['hanzi' => '你好', 'pinyin' => 'nǐ hǎo', 'meaning' => 'Halo'],
            ['hanzi' => '谢谢', 'pinyin' => 'xiè xiè', 'meaning' => 'Terima kasih'],
            ['hanzi' => '再见', 'pinyin' => 'zài jiàn', 'meaning' => 'Sampai jumpa'],
            ['hanzi' => '老师', 'pinyin' => 'lǎo shī', 'meaning' => 'Guru'],
            ['hanzi' => '学生', 'pinyin' => 'xué shēng', 'meaning' => 'Murid'],
            ['hanzi' => '朋友', 'pinyin' => 'péng yǒu', 'meaning' => 'Teman'],
        ]);

        return [
            'order_number' => fake()->numberBetween(1, 5),
            'hanzi' => $vocab['hanzi'],
            'pinyin' => $vocab['pinyin'],
            'meaning' => $vocab['meaning'],
            'example_sentence' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
