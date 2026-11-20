<?php

namespace App\Support;

class SelfStudyScore
{
    public static function make(int $vocabStars, int $vocabMax, int $grammarStars, int $grammarMax): array
    {
        $set     = \App\Models\SelfStudySetting::current();
        $wV      = $set['weight_vocab'];
        $wG      = $set['weight_grammar'];
        $passing = $set['passing_score'];

        // komponen yang belum punya bintang maksimal tidak ikut dihitung
        $wV = $vocabMax > 0 ? $wV : 0;
        $wG = $grammarMax > 0 ? $wG : 0;
        $sum = max(1, $wV + $wG);

        $vp = $vocabMax > 0 ? $vocabStars / $vocabMax * 100 : 0;
        $gp = $grammarMax > 0 ? $grammarStars / $grammarMax * 100 : 0;

        $total = (int) round(($vp * $wV + $gp * $wG) / $sum);

        $predikat = 'Belum lulus';

        $levels   = config('selfstudy.predikat', []);
        krsort($levels);
        foreach ($levels as $min => $label) {
            if ($total >= $min) {
                $predikat = $label;
            if ($total >= $passing && $predikat === 'Belum lulus') {
                $predikat = 'Cukup';
}
                break;
            }
        }

        return [
            'vocab_pct'      => (int) round($vp),
            'grammar_pct'    => (int) round($gp),
            'total'          => $total,
            'predikat'       => $predikat,
            'lulus'          => $total >= $passing,
            'passing'        => $passing,
            'weight_vocab'   => (int) round($wV / $sum * 100),
            'weight_grammar' => (int) round($wG / $sum * 100),
        ];
    }
}