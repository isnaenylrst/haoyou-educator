<?php
namespace App\Services;

use App\Models\VocabWord;
use Illuminate\Support\Str;

/**
 * Penilaian jawaban Test di server (otomatis): hafal / salah, atau ragu jika dicentang.
 *
 * Kolom yang diisi siswa per tes:
 *   reading   : pinyin, tone, meaning   (hanzi ditampilkan)
 *   listening : pinyin, tone, meaning   (audio diputar)
 *   writing   : hanzi,  pinyin, tone    (arti Indonesia & English ditampilkan)
 * HAFAL hanya jika SEMUA kolom benar.
 */
class VocabTestEvaluator
{
    public const FIELDS = [
        'reading'   => ['pinyin', 'tone', 'meaning'],
        'listening' => ['pinyin', 'tone', 'meaning'],
        'writing'   => ['hanzi', 'pinyin', 'tone'],
    ];

    /** huruf kecil, tanpa nada pinyin & spasi */
    public static function norm(?string $s): string
    {
        $s = mb_strtolower(trim((string) $s));
        if (class_exists(\Normalizer::class)) {
            $s = preg_replace('/\p{Mn}+/u', '', \Normalizer::normalize($s, \Normalizer::FORM_D));
        } else {
            $s = Str::ascii($s);
        }
        return preg_replace('/\s+/u', '', $s);
    }

    /* ------------------------------ ARTI ------------------------------ */
    private static function meaningOk(string $answer, VocabWord $w): bool
    {
        $cands = preg_split('/[\/,;]/u', $w->meaning_id . '/' . $w->meaning_en);
        $cands = array_filter(array_map([self::class, 'norm'], $cands));
        return in_array(self::norm($answer), $cands, true);
    }

    /* ----------------------------- PINYIN ----------------------------- */
    /** pinyin dibandingkan tanpa nada, tanpa spasi, dan tanpa angka (kalau siswa menulis ni3 hao3) */
    private static function pinyinOk(string $answer, VocabWord $w): bool
    {
        $clean = fn ($s) => self::norm(preg_replace('/\d+/', '', (string) $s));
        $a = $clean($answer);
        return $a !== '' && $a === $clean($w->pinyin);
    }

    /* ------------------------------ NADA ------------------------------ */
    private const TONE_MARKS = [
        'ā' => 1, 'ē' => 1, 'ī' => 1, 'ō' => 1, 'ū' => 1, 'ǖ' => 1,
        'á' => 2, 'é' => 2, 'í' => 2, 'ó' => 2, 'ú' => 2, 'ǘ' => 2,
        'ǎ' => 3, 'ě' => 3, 'ǐ' => 3, 'ǒ' => 3, 'ǔ' => 3, 'ǚ' => 3,
        'à' => 4, 'è' => 4, 'ì' => 4, 'ò' => 4, 'ù' => 4, 'ǜ' => 4,
    ];

    /** input siswa → deretan angka nada, contoh "3 2" / "3-2" / "32" → "32"; 0 dianggap netral (5) */
    public static function toneDigits(?string $s): string
    {
        return str_replace('0', '5', preg_replace('/[^0-5]/', '', (string) $s));
    }

    /** nada yang benar, diambil dari pinyin kata: "nǐ hǎo" → "32", "māma" → "15" */
    public static function expectedTones(?string $pinyin): string
    {
        $p = mb_strtolower(trim((string) $pinyin));
        if ($p === '') return '';
        if (class_exists(\Normalizer::class)) $p = \Normalizer::normalize($p, \Normalizer::FORM_C);

        if (preg_match('/\d/', $p)) return self::toneDigits($p);       // pinyin tersimpan sebagai ni3 hao3

        $out = '';
        foreach (preg_split('/[\s\'’\-]+/u', $p, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (preg_match_all('/[āēīōūǖáéíóúǘǎěǐǒǔǚàèìòùǜ]/u', $token, $m)) {
                foreach ($m[0] as $ch) $out .= self::TONE_MARKS[$ch];
            } else {
                $out .= '5';                                            // tanpa tanda = nada netral
            }
        }
        return $out;
    }

    private static function toneOk(string $answer, VocabWord $w): bool
    {
        $exp = self::expectedTones($w->pinyin);
        return $exp === '' ? true : self::toneDigits($answer) === $exp;
    }

    /* ------------------------------ HANZI ------------------------------ */
    private static function hanziOk(string $answer, VocabWord $w): bool
    {
        $clean = fn ($s) => preg_replace('/\s+/u', '', (string) $s);
        $a = $clean($answer);
        return $a !== '' && $a === $clean($w->hanzi);
    }

    /**
     * @param  array $a  jawaban siswa, key: pinyin, tone, hanzi, meaning
     * @return string|null  hafal | ragu | salah | null (kosong, belum dijawab)
     */
    public static function evaluate(string $type, VocabWord $w, array $a, bool $ragu): ?string
    {
        if ($ragu) return 'ragu';

        $fields = self::FIELDS[$type];
        $filled = false;
        foreach ($fields as $f) {
            if (trim((string) ($a[$f] ?? '')) !== '') { $filled = true; break; }
        }
        if (! $filled) return null;

        foreach ($fields as $f) {
            $v  = (string) ($a[$f] ?? '');
            $ok = match ($f) {
                'pinyin'  => self::pinyinOk($v, $w),
                'tone'    => self::toneOk($v, $w),
                'hanzi'   => self::hanziOk($v, $w),
                'meaning' => self::meaningOk($v, $w),
            };
            if (! $ok) return 'salah';
        }
        return 'hafal';
    }
}