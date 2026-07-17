<?php

namespace Database\Factories;

use App\Models\LearningMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

class VocabularyFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $vocabularies = [

            ['hanzi'=>'你好','pinyin'=>'Nǐ hǎo','meaning'=>'Halo'],
            ['hanzi'=>'谢谢','pinyin'=>'Xièxie','meaning'=>'Terima kasih'],
            ['hanzi'=>'老师','pinyin'=>'Lǎoshī','meaning'=>'Guru'],
            ['hanzi'=>'学生','pinyin'=>'Xuéshēng','meaning'=>'Siswa'],
            ['hanzi'=>'再见','pinyin'=>'Zàijiàn','meaning'=>'Sampai jumpa'],
            ['hanzi'=>'早上好','pinyin'=>'Zǎoshang hǎo','meaning'=>'Selamat pagi'],
            ['hanzi'=>'晚上好','pinyin'=>'Wǎnshang hǎo','meaning'=>'Selamat malam'],
            ['hanzi'=>'请','pinyin'=>'Qǐng','meaning'=>'Silakan'],
            ['hanzi'=>'对不起','pinyin'=>'Duìbuqǐ','meaning'=>'Maaf'],
            ['hanzi'=>'没关系','pinyin'=>'Méi guānxi','meaning'=>'Tidak apa-apa'],

        ];

        $word = fake()->randomElement($vocabularies);

        return [

            'learning_material_id' => LearningMaterial::factory(),

            'hanzi' => $word['hanzi'],

            'pinyin' => $word['pinyin'],

            'meaning' => $word['meaning'],

            'example' => fake()->sentence(),

        ];
    }
}