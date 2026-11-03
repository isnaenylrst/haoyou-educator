<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\GrammarProgress;
use App\Support\GrammarData;
use Illuminate\Http\Request;

class GrammarController extends Controller
{
    public function guide()
{
    $uid       = auth()->id();
    $questions = collect(GrammarData::questions())->keyBy('no');

    $learned = GrammarProgress::where('user_id', $uid)->where('kind', 'list')->count();
    $answers = GrammarProgress::where('user_id', $uid)->where('kind', 'test')->pluck('choice', 'item_no');

    $benar = $answers->filter(
        fn ($choice, $no) => ($questions[$no]['options'][$choice][1] ?? null) === 'benar'
    )->count();

    // Ambil ID video dari berbagai format link YouTube
    $videoId = null;
    $url = config('services.grammar.video_url');
    if ($url && preg_match('~(?:v=|youtu\.be/|embed/|shorts/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        $videoId = $m[1];
    }

    return view('Siswa.grammar.guide', [
        'title'    => 'Panduan',
        'parent'   => 'Belajar Grammar',
        'videoId'  => $videoId,
        'progress' => [
            'learned'    => $learned,
            'total_list' => count(GrammarData::list()),
            'tested'     => $answers->count(),
            'total_test' => $questions->count(),
            'benar'      => $benar,
        ],
    ]);
}
    public function list()
    {
        $items   = GrammarData::list();
        $learned = GrammarProgress::where('user_id', auth()->id())
            ->where('kind', 'list')->pluck('item_no')->all();

        return view('siswa.grammar.list', [
            'title'    => 'Grammar List',
            'parent'   => 'Belajar Grammar',
            'subtitle' => 'Daftar pola grammar lengkap dengan fungsi, pola, dan contoh kalimat.',
            'items'    => $items,
            'learned'  => $learned,
            'total'    => count($items),
        ]);
    }

    public function toggle(int $no)
    {
        $row = GrammarProgress::where([
            'user_id' => auth()->id(), 'kind' => 'list', 'item_no' => $no,
        ])->first();

        if ($row) {
            $row->delete();
            $on = false;
        } else {
            GrammarProgress::create([
                'user_id' => auth()->id(), 'kind' => 'list', 'item_no' => $no,
            ]);
            $on = true;
        }

        $count = GrammarProgress::where('user_id', auth()->id())->where('kind', 'list')->count();

        return response()->json(['on' => $on, 'count' => $count]);
    }

    public function traps()
    {
        return view('siswa.grammar.traps', [
            'title'    => 'Grammar Traps',
            'parent'   => 'Belajar Grammar',
            'subtitle' => 'Grammar atau kata yang sering bikin salah walaupun kelihatannya gampang.',
            'traps'    => GrammarData::traps(),
        ]);
    }

    public function test()
    {
        $questions = GrammarData::questions();
        $answers   = GrammarProgress::where('user_id', auth()->id())
            ->where('kind', 'test')->pluck('choice', 'item_no')->all();

        return view('siswa.grammar.test', [
            'title'     => 'Grammar Test',
            'parent'    => 'Belajar Grammar',
            'subtitle'  => 'Pilih jawaban hanzi yang benar untuk tiap soal.',
            'questions' => $questions,
            'answers'   => $answers,
            'total'     => count($questions),
        ]);
    }

    public function answer(Request $request, int $no)
    {
        $data = $request->validate(['choice' => 'required|integer|min:0|max:9']);

        $q = collect(GrammarData::questions())->firstWhere('no', $no);
        abort_if(!$q || !isset($q['options'][$data['choice']]), 404);

        GrammarProgress::updateOrCreate(
            ['user_id' => auth()->id(), 'kind' => 'test', 'item_no' => $no],
            ['choice' => $data['choice']]
        );

        $answered = GrammarProgress::where('user_id', auth()->id())->where('kind', 'test')->count();
        [$text, $status] = $q['options'][$data['choice']];

        return response()->json([
            'text'     => $text,
            'status'   => $status,
            'answered' => $answered,
        ]);
    }
}