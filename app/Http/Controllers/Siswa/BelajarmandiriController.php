<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\GrammarProgress;
use App\Models\VocabTestResult;
use App\Models\VocabWord;
use App\Support\GrammarData;

class BelajarMandiriController extends Controller
{
    /** Halaman hub: 3 kartu (Vocab, Grammar, Sertifikat mandiri). */
    public function index()
    {
        $user  = auth()->user();
        $uid   = $user->id;
        $level = $user->program_level_id ?? null;

        // ---- Vocab (rumus sama dengan VocabController@list) ----
        $vocabTotal   = VocabWord::forLevel($level)->where('trap_only', false)->count();
        $vocabLearned = VocabTestResult::where('user_id', $uid)->where('status', 'hafal')
            ->whereHas('word', fn ($w) => $w->forLevel($level))
            ->distinct()->count('vocab_word_id');

        // ---- Grammar (rumus sama dengan GrammarController@list) ----
        $grammarTotal   = count(GrammarData::list());
        $grammarLearned = GrammarProgress::where('user_id', $uid)->where('kind', 'list')->count();

        $pct = fn ($done, $total) => $total > 0 ? (int) min(100, round($done / $total * 100)) : 0;

        return view('Siswa.belajar-mandiri.index', [
            'title'      => 'Belajar mandiri',
            'subtitle'   => 'Pilih materi yang ingin kamu pelajari sendiri, kapan saja.',
            'vocabPct'   => $pct($vocabLearned, $vocabTotal),
            'grammarPct' => $pct($grammarLearned, $grammarTotal),

            // TODO (tahap sertifikat): isi dengan jumlah sertifikat mandiri siswa
            'sertifikatMandiri' => 0,
        ]);
    }
}