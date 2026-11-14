<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\SelfStudyCertificate;
use App\Support\SelfStudyReport;

class BelajarMandiriController extends Controller
{
    /** Halaman hub: ringkasan bintang + 3 kartu (Vocab, Grammar, Sertifikat mandiri). */
    public function index()
    {
        $uid = auth()->id();
        $s   = SelfStudyReport::summary($uid);

        $pct = fn ($done, $total) => $total > 0 ? (int) min(100, round($done / $total * 100)) : 0;

        return view('Siswa.belajar-mandiri.index', [
            'title'      => 'Belajar mandiri',
            'subtitle'   => 'Pilih materi yang ingin kamu pelajari sendiri, kapan saja.',
            'vocabPct'   => $pct($s['vocab_learned'], $s['vocab_total']),
            'grammarPct' => $pct($s['grammar_learned'], $s['grammar_total']),

            'vocabStars'   => $s['vocab_stars'],
            'vocabMax'     => $s['vocab_max'],
            'grammarStars' => $s['grammar_stars'],
            'grammarMax'   => $s['grammar_max'],
            'score'        => $s['score'],

            'sertifikatMandiri' => SelfStudyCertificate::where('user_id', $uid)->count(),
        ]);
    }
}