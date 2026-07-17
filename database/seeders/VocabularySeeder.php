<?php

namespace Database\Seeders;

use App\Models\Vocabulary;
use Illuminate\Database\Seeder;

class VocabularySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Vocabulary::insert([

            [
                'learning_material_id'=>1,
                'hanzi'=>'你好',
                'pinyin'=>'Nǐ hǎo',
                'meaning'=>'Halo',
                'example'=>'你好，我叫小明。',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            [
                'learning_material_id'=>1,
                'hanzi'=>'谢谢',
                'pinyin'=>'Xièxie',
                'meaning'=>'Terima kasih',
                'example'=>'谢谢老师。',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            [
                'learning_material_id'=>1,
                'hanzi'=>'老师',
                'pinyin'=>'Lǎoshī',
                'meaning'=>'Guru',
                'example'=>'王老师很好。',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            [
                'learning_material_id'=>2,
                'hanzi'=>'学生',
                'pinyin'=>'Xuéshēng',
                'meaning'=>'Siswa',
                'example'=>'我是学生。',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            [
                'learning_material_id'=>2,
                'hanzi'=>'再见',
                'pinyin'=>'Zàijiàn',
                'meaning'=>'Sampai jumpa',
                'example'=>'老师，再见！',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

        ]);

    }
}