<?php
namespace App\Services;

use App\Models\VocabWord;
use Illuminate\Support\Str;

/** Penilaian jawaban Test di server (otomatis): hafal / salah, atau ragu jika dicentang. */
class VocabTestEvaluator
{
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

    private static function meaningOk(string $answer, VocabWord $w): bool
    {
        $cands = preg_split('/[\/,;]/u', $w->meaning_id . '/' . $w->meaning_en);
        $cands = array_filter(array_map([self::class, 'norm'], $cands));
        return in_array(self::norm($answer), $cands, true);
    }

    /** @return string|null  hafal | ragu | salah | null (kosong, belum dijawab) */
    public static function evaluate(string $type, VocabWord $w, ?string $answer, ?string $extra, bool $ragu): ?string
    {
        if ($ragu) return 'ragu';

        if ($type === 'writing') {                       // answer = pinyin, extra = hanzi
            if (trim((string) $answer) === '' && trim((string) $extra) === '') return null;
            $ok = self::norm($answer) === self::norm($w->pinyin) && trim((string) $extra) === $w->hanzi;
            return $ok ? 'hafal' : 'salah';
        }

        if (trim((string) $answer) === '') return null;  // reading & listening: ketik arti
        return self::meaningOk($answer, $w) ? 'hafal' : 'salah';
    }
}
