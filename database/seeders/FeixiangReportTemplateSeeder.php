<?php

namespace Database\Seeders;

use App\Models\ProgramCategory;
use App\Models\ProgressReportTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Template Learning Materials Feixiang (12 pertemuan) dari PDF contoh "Progress Report HANA FDX".
 * Aman dijalankan ulang: tidak menimpa template Feixiang yang sudah ada.
 *   php artisan db:seed --class=FeixiangReportTemplateSeeder
 */
class FeixiangReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $category = ProgramCategory::where('category_name', 'Feixiang')->first();
        if (!$category) {
            $this->command?->warn('Kategori program "Feixiang" tidak ditemukan; seeder dilewati.');
            return;
        }

        if (ProgressReportTemplate::where('program_category_id', $category->id)->whereNull('program_level_id')->exists()) {
            $this->command?->info('Template Feixiang sudah ada; tidak ditimpa.');
            return;
        }

        $meetings = [
            1 => <<<'T'
Student learned the differences between 中文, 汉语, and 普通话.
Student learned the differences between Hanzi and Pinyin.
Student were able to distinguish between Tone 1, Tone 2, Tone 3, Tone 4, and the neutral tone.
Student understood the tone changes when the word "yi (一)" and "bu (不)" were followed by Tone 1, Tone 2, or Tone 3, in which case "yi (一)" and "bu (不)" were pronounced with Tone 4.
When "yi (一)" and "bu (不)" were placed between phrases, they were pronounced with the neutral tone (轻声).
When "yi (一)" and "bu (不)" were followed by Tone 4, they were pronounced with Tone 2.
Student understood the pronunciation of vowel sounds (a, o, e, i, u).
Student understood the pronunciation of consonant sounds (b, p, d, t).
Student learned pronunciation and tones with vocabulary:
一，二，十，王，八，习，口，你，子，人，三，回，老师，谢谢，明天见，我，叫，她，他，生，水，国，问，四
T,
            2 => <<<'T'
Student understood the pronunciation of vowel sounds (ai, ei, ao, ou, iu, ie, iao, ua, uan, uang, an, in, ang, eng, ing, ong).
Student understood the pronunciation of consonant sounds (m, f, g, k, h, r, w).
Student practiced pronunciation with tones.
Student learned pronunciation and tones with vocabulary:
喝，树，打，说，花，吃，茶，妈，河，再见，语，您，好，听，什么
T,
            3 => <<<'T'
Student understood the pronunciation of vowel sounds (ui, er, en, un, uo, uai).
Student understood the pronunciation of consonant sounds (z, c, s, zh, ch, sh).
Student practiced pronunciation with tones.
Student learned pronunciation and tones with vocabulary:
学生，爸爸，写，是，看，买，去，书，米饭，手机，东西，音乐，鞋子，不，没
Student learned pronunciation with tongue twister:
四是四十是十十四是十四四十是四十.
T,
            4 => <<<'T'
Student understood the pronunciation of vowel sounds (iong, ian, ü, üe, ün).
Student understood the pronunciation of consonant sounds (j, q, x, l, y, n).
Student practiced pronunciation with tones.
Student Review and learned pronunciation and tones with vocabulary:
一，二，十，王，八，习，口，你，子，人，三，回，老师，谢谢，明天见，我，叫，她，他，生，水，国，问，四，喝，树，打，说，花，吃，茶，妈，河，再见，语，您，好，听，什么，学生，爸爸，写，是，看，买，去，书，米饭，手机，东西，音乐，鞋子，不，没，有，喜欢，苹果，电视，学校，面条，吗，还是，在
Student learned pronunciation with tongue twister:
吃葡萄不吐葡萄皮,不吃葡萄倒吐葡萄皮.
有个小孩叫巧巧,巧巧哥哥叫乔乔,乔乔划船带 巧巧,巧巧乔乔看姥姥.
Review: Student is assessed for their ability in listening and pronouncing the previously learned vowels and consonants.
T,
            5 => <<<'T'
Student learned basic greetings:
大家，现在，们，我们，你们，他们，朋友，来自，印尼，中国，工作，员工，大学生.
Student learned how to introduce their names:
我叫…/我的名字是…
Student learned how to ask for someone’s name:
你叫什么名字/你的名字是什么？
Student learned Chinese names and surnames
林明杰, 李成, 王海.
1. 演员：成龙, 李小龙, 刘亦菲, 谭松韵, 言承旭
2. 歌手：周兴哲, 张艺兴, TFBOYS (王俊凯, 王源, 易烊千玺), 庾澄庆
3. 运动员：王楚钦，樊振东, 黄雨婷
4. 商人：马云 (Alibaba), 柳传志 (Lenovo), 雷军 (Xiaomi)
5. 厨师：喻波
6. 流星花园：道明寺, 花澤類, 西门彦, 冯美作, 董杉菜
Student learned job-related vocabulary:
Student learned the usage of plural marker (们)
Student learned the usage of “de” (的)
Student learned how to politely ask for someone’s surname:
请问，您贵姓？
T,
            6 => <<<'T'
Student learned numbers from 1-10:
一，二，三，四，五，六，七，八，九，十. 两, 一百
Student learned the usage of "liang" (两)
Student learned measure words:
个，本，支，张，只，把 .
Student learned new vocabulary:
笔, 纸, 猫,伞,词典,铅笔, 桌子,剪刀, 班上,多少,几,岁,多大
Student learned classifier words:.
Student learned the usage of "you" (有)
Student learned a new grammar
多少 + 量词+名词
T,
            7 => <<<'T'
Student learned about colors
爱好，唱歌，运动，踢足球，打篮球，听音乐，跳舞，旅行，看书，玩游戏，做饭，画画
Student learned the usage of "hen"
S + 喜欢 + N
*S + 的爱好 + 是 + Hobby
T,
            8 => <<<'T'
Review: Student is assessed on their ability to apply vocabulary and grammar from past lessons in basic conversation:
- Tones，Pronunciation，Measure Words，Introduce Yourself
- The students learn new vocabulary:
漂亮，帅，高，矮，老，年轻，胖，瘦，短发，长发，奶奶，歌手，创始人，主席，演员，运动员，非常，很，特别，最
T,
            9 => <<<'T'
Students learn about:
从，来，的，在，哪里，大学，公司，咖啡厅，医院，银行，商店，印尼
Student learned to use :
S + 在 + 地方 (place) + Verb
S + 从 + 地方 (place) + 来
T,
            10 => <<<'T'
Student learned about
小，重，轻，长，短，硬，软，厚，薄，干净，肮脏，这，那，衣服，沙发，床
student learned to use :
*这 + /量词+ N + (很+Adj)
*那 +量词+ N + (很+Adj)
T,
            11 => <<<'T'
Student learn vocabulary related to
文化，剪纸，为什么，装饰，家，和，小吃，包子，做，糖葫芦，
The students learn to use:
S + 为什么 + Verb + Objek？
T,
            12 => <<<'T'
Review: Student is assessed on their ability to apply vocabulary and grammar from past lessons in basic conversation:
-Basic foundation
-Number and Measure word
-Name
-Hobbies
- Culture
T,
        ];

        DB::transaction(function () use ($category, $meetings) {
            $template = ProgressReportTemplate::create([
                'name'                => 'Feixiang 读写',
                'program_category_id' => $category->id,
                'program_level_id'    => null,
            ]);
            foreach ($meetings as $no => $text) {
                $template->meetings()->create(['meeting_number' => $no, 'materials' => trim($text)]);
            }
        });

        $this->command?->info('Template Feixiang (12 pertemuan) dibuat.');
    }
}