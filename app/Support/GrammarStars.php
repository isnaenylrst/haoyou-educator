<?php

namespace App\Support;

use App\Models\GrammarProgress;

class GrammarStars
{
    public const MAX_PER_POLA = 3;

    /** benar = 1, kurang tepat = 0.5, salah / belum dijawab = 0 */
    public static function poin(?string $status): float
    {
        if ($status === null) {
            return 0.0;
        }
        $s = mb_strtolower(trim($status));
        if ($s === 'benar') {
            return 1.0;
        }
        if ($s === 'salah' || $s === '') {
            return 0.0;
        }
        return 0.5; // 'kurang' atau status lain
    }

    /**
     * Bintang per pola = min(3, jumlah poin dari soal-soal pola itu).
     * Bintang maksimal per pola = min(3, jumlah soal pola itu), pola tanpa soal tidak dihitung.
     *
     * @return array{per_pola: array<int,int>, max_per_pola: array<int,int>, total: int, max: int}
     */
    public static function forUser(int $uid): array
    {
        $questions = GrammarData::questions();
        $answers   = GrammarProgress::where('user_id', $uid)
            ->where('kind', 'test')
            ->pluck('choice', 'item_no');

        $soalPerPola = [];
        $points      = [];

        foreach ($questions as $q) {
            $polaNo = $q['grammar_no'] ?? null;
            if (!$polaNo) {
                continue;
            }
            $soalPerPola[$polaNo] = ($soalPerPola[$polaNo] ?? 0) + 1;

            if ($answers->has($q['no'])) {
                $status = $q['options'][$answers[$q['no']]][1] ?? null;
                $points[$polaNo] = ($points[$polaNo] ?? 0) + self::poin($status);
            }
        }

        $perPola = [];
        $maxPola = [];
        $max     = 0;

        foreach (GrammarData::list() as $g) {
            $cap = min(self::MAX_PER_POLA, $soalPerPola[$g['no']] ?? 0);
            $maxPola[$g['no']] = $cap;
            $perPola[$g['no']] = min($cap, (int) floor($points[$g['no']] ?? 0));
            $max += $cap;
        }

        return [
            'per_pola'     => $perPola,
            'max_per_pola' => $maxPola,
            'total'        => array_sum($perPola),
            'max'          => $max,
        ];
    }
}