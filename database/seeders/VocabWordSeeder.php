<?php

namespace Database\Seeders;

use App\Models\VocabExample;
use App\Models\VocabTrap;
use App\Models\VocabWord;
use Illuminate\Database\Seeder;

/**
 * Data contoh dari mockup. Untuk 1599 vocab HSK asli: import CSV ke vocab_words
 * (kolom: order_number,hanzi,pinyin,meaning_id,meaning_en,word_type,trap_only).
 */
class VocabWordSeeder extends Seeder
{
    public function run(): void
    {
        // [hanzi, pinyin, jenis, arti_id, arti_en, trap_only(*), [[kalimat, pinyin, arti], ...]]
        $words = [
            ['唉','ài','叹','aduh','alas',1,[['唉，他走了。','āi, tā zǒu le.','Aduh, dia pergi.'],['唉，我又迟到了。','ài, wǒ yòu chídào le.','Aduh, aku terlambat lagi.']]],
            ['哎','āi','叹','hei','hey',0,[['哎，你在这儿！','āi, nǐ zài zhèr!','Hei, kamu di sini!'],['哎，那是什么？','āi, nà shì shénme?','Hei, itu apa?']]],
            ['爱护','ài hù','动','menyayangi','to cherish',1,[['我们要爱护环境。','Wǒmen yào àihù huánjìng.','Kita harus menyayangi lingkungan.'],['请爱护公物。','Qǐng àihù gōngwù.','Tolong jaga fasilitas umum.']]],
            ['哎呀','āi yā','叹','aduh','oh dear',1,[['哎呀，忘带手机了。','Āi yā, wàng dài shǒujī le.','Aduh, lupa bawa ponsel.'],['哎呀，下雨了！','Āi yā, xià yǔ le!','Aduh, hujan!']]],
            ['暗','àn','形','gelap','dark',0,[['房间很暗。','Fángjiān hěn àn.','Kamarnya gelap.'],['天暗下来了。','Tiān àn xiàlái le.','Hari mulai gelap.']]],
            ['安','ān','形、动','damai','peaceful',0,[['他心很安。','Tā xīn hěn ān.','Hatinya tenang.'],['我心里不安。','Wǒ xīnlǐ bù ān.','Hatiku gelisah.']]],
            ['安全带','ān quán dài','名','sabuk pengaman','seatbelt',0,[['请系好安全带。','Qǐng jì hǎo ānquándài.','Tolong pakai sabuk pengaman.'],['开车要系安全带。','Kāichē yào jì ānquándài.','Saat menyetir harus pakai sabuk pengaman.']]],
            ['安慰','ān wèi','动、形','menghibur','to comfort',1,[['他安慰了朋友。','Tā ānwèi le péngyou.','Dia menghibur temannya.'],['妈妈安慰哭泣的孩子。','Māma ānwèi kūqì de háizi.','Ibu menghibur anak yang menangis.'],['你的话安慰了我。','Nǐ de huà ānwèi le wǒ.','Kata-katamu menghiburku.']]],
            ['安装','ān zhuāng','动','memasang','to install',0,[['我在安装软件。','Wǒ zài ānzhuāng ruǎnjiàn.','Aku sedang memasang software.'],['师傅来安装空调。','Shīfu lái ānzhuāng kōngtiáo.','Teknisi datang memasang AC.']]],
            ['熬夜','áo yè','动','begadang','to stay up late',0,[['别熬夜了。','Bié áoyè le.','Jangan begadang.'],['熬夜对身体不好。','Áoyè duì shēntǐ bù hǎo.','Begadang tidak baik untuk tubuh.']]],
            ['白','bái','形、副','putih','white',0,[['这件衣服是白色的。','Zhè jiàn yīfu shì báisè de.','Baju ini berwarna putih.'],['墙是白的。','Qiáng shì bái de.','Temboknya putih.']]],
            ['傍晚','bàng wǎn','名','senja','dusk',0,[['傍晚我们去散步。','Bàngwǎn wǒmen qù sànbù.','Sore hari kita jalan-jalan.'],['傍晚下起了雨。','Bàngwǎn xià qǐ le yǔ.','Menjelang malam hujan turun.']]],
            ['半夜','bàn yè','数量、名','tengah malam','midnight',0,[['他半夜才回家。','Tā bànyè cái huíjiā.','Dia pulang tengah malam.'],['半夜有人敲门。','Bànyè yǒu rén qiāo mén.','Tengah malam ada yang mengetuk pintu.']]],
            ['薄','báo','形','tipis','thin',0,[['这本书很薄。','Zhè běn shū hěn báo.','Buku ini tipis.'],['外套太薄了。','Wàitào tài báo le.','Jaketnya terlalu tipis.']]],
            ['宝','bǎo','名','harta karun','treasure',0,[['这是我的宝贝。','Zhè shì wǒ de bǎobèi.','Ini harta karunku.']]],
            ['保','bǎo','动','melindungi','to protect',0,[['我们要保护环境。','Wǒmen yào bǎohù huánjìng.','Kita harus melindungi lingkungan.']]],
            // --- khusus Vocab Traps (tanda *) ---
            ['珍惜','zhēn xī','动','menghargai','to treasure',1,[['请珍惜现在的生活。','Qǐng zhēnxī xiànzài de shēnghuó.','Tolong hargai kehidupan sekarang.'],['要珍惜时间。','Yào zhēnxī shíjiān.','Harus menghargai waktu.']]],
            ['娱乐','yú lè','动、名','menghibur','entertainment',1,[['看电影是种娱乐。','Kàn diànyǐng shì zhǒng yúlè.','Menonton film adalah hiburan.'],['这里娱乐活动很多。','Zhèlǐ yúlè huódòng hěn duō.','Di sini banyak kegiatan hiburan.']]],
            ['称','chēng','动','menyebut','to call',1,[['人们称他老师。','Rénmen chēng tā lǎoshī.','Orang-orang menyebut dia guru.'],['同学们称他大哥。','Tóngxuémen chēng tā dàgē.','Teman-teman menyebutnya kakak.']]],
            ['称','chēng','动','menimbang','to weigh',1,[['称体重。','Chēng tǐzhòng.','Menimbang berat badan.'],['请帮我称一下苹果。','Qǐng bāng wǒ chēng yíxià píngguǒ.','Tolong timbangkan apelnya.']]],
            ['还','hái','副','masih','still',1,[['他还没到。','Tā hái méi dào.','Dia masih belum sampai.'],['我还想吃。','Wǒ hái xiǎng chī.','Aku masih mau makan.']]],
            ['还','huán','动','mengembalikan','to return',1,[['我还你钱。','Wǒ huán nǐ qián.','Aku mengembalikan uangmu.'],['书要按时还。','Shū yào ànshí huán.','Buku harus dikembalikan tepat waktu.']]],
        ];

        $ids = [];
        foreach ($words as $i => [$h, $p, $type, $id, $en, $trapOnly, $ex]) {
            $w = VocabWord::updateOrCreate(
                ['hanzi' => $h, 'pinyin' => $p, 'meaning_id' => $id],
                ['order_number' => $i + 1, 'meaning_en' => $en, 'word_type' => $type, 'trap_only' => (bool) $trapOnly]
            );
            $w->examples()->delete();
            foreach ($ex as $n => [$sh, $sp, $sm]) {
                VocabExample::create(['vocab_word_id' => $w->id, 'sentence_hanzi' => $sh, 'sentence_pinyin' => $sp, 'sentence_meaning' => $sm, 'sort_order' => $n]);
            }
            $ids[$h . '|' . $p . '|' . $id] = $w->id;
        }

        // Vocab Traps: [kunci kata, tipe, group, penjelasan[]]
        $traps = [
            ['唉|ài|aduh','same_meaning','aduh',['Kata seru untuk mengekspresikan perasaan','Menunjukkan keluhan atau penyesalan','Nada emosional tergantung konteks']],
            ['哎呀|āi yā|aduh','same_meaning','aduh',['Kata seru untuk reaksi spontan','Dipakai saat kaget atau kesal ringan','Lebih ekspresif dari 唉']],
            ['爱护|ài hù|menyayangi','same_meaning','menyayangi',['Menyayangi dengan menjaga dan melindungi','Menunjukkan rasa peduli aktif','Lebih formal dari 爱']],
            ['珍惜|zhēn xī|menghargai','same_meaning','menyayangi',['Menghargai sesuatu yang berharga','Biasanya karena sulit didapat','Mengandung makna emosional']],
            ['安慰|ān wèi|menghibur','same_meaning','menghibur',['Memberi rasa tenang pada orang lain','Dipakai sebagai kata kerja dan sifat','Umum dalam situasi sedih']],
            ['娱乐|yú lè|menghibur','same_meaning','menghibur',['Kegiatan untuk bersenang-senang','Bisa sebagai kata kerja atau benda']],
            ['称|chēng|menyebut','same_hanzi','称',null],
            ['称|chēng|menimbang','same_hanzi','称',null],
            ['还|hái|masih','same_hanzi','还',null],
            ['还|huán|mengembalikan','same_hanzi','还',null],
        ];
        foreach ($traps as $n => [$key, $type, $group, $exp]) {
            VocabTrap::updateOrCreate(
                ['vocab_word_id' => $ids[$key], 'type' => $type],
                ['group_key' => $group, 'explanations' => $exp, 'sort_order' => $n + 1]
            );
        }
    }
}
