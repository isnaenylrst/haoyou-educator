<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\VocabStudyPlan;
use App\Models\VocabTestResult;
use App\Models\VocabTrap;
use App\Models\VocabTrapUnderstood;
use App\Models\VocabWord;
use App\Services\VocabTestEvaluator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VocabController extends Controller
{
    private const TYPES = ['reading', 'listening', 'writing'];

    /* ============================ STUDY PLAN ============================ */
    public function plan()
{
    $uid = auth()->id();
    $plan = VocabStudyPlan::where('user_id', $uid)->first();

    $hafalQuery = VocabTestResult::where('user_id', $uid)->where('status', 'hafal');

    return view('Siswa.vocab.plan', [
        'title'   => 'Belajar Vocab › Study Plan',
        'plan'    => $plan,
        'total'   => VocabWord::count(),
        'minutes' => [5 => '5 menit', 10 => '10 menit', 15 => '15 menit', 20 => '20 menit', 30 => '30 menit',
                      45 => '45 menit', 60 => '1 jam', 90 => '1,5 jam', 120 => '2 jam', 180 => '3 jam'],

        // dipakai untuk menandai langkah mana yang sudah selesai
        'progress' => [
            'plan'    => (bool) $plan,
            'tested'  => VocabTestResult::where('user_id', $uid)->count(),
            'learned' => (clone $hafalQuery)->distinct()->count('vocab_word_id'),
            'stars'   => (clone $hafalQuery)->count(),
            'paham'   => VocabTrapUnderstood::where('user_id', $uid)->count(),
        ],
    ]);
}

    public function planSave(Request $r)
    {
        $d = $r->validate([
            'start_date'    => 'required|date',
            'words_per_day' => 'required|integer|min:1|max:500',
            'end_date'      => 'required|date|after:start_date',
            'daily_minutes' => 'required|integer|in:5,10,15,20,30,45,60,90,120,180',
        ]);
        $d['duration_days'] = (int) Carbon::parse($d['start_date'])->diffInDays(Carbon::parse($d['end_date']));
        VocabStudyPlan::updateOrCreate(['user_id' => auth()->id()], $d);

        return back()->with('ok', 'Rencana belajar tersimpan');
    }

    /* ============================ VOCAB LIST ============================ */
    public function list(Request $r)
    {
        $uid = auth()->id();
        $hafal = fn ($q) => $q->where('user_id', $uid)->where('status', 'hafal');

        $q = VocabWord::with('examples')->orderBy('order_number');
        if ($s = trim((string) $r->q)) {
            $q->where(fn ($w) => $w->where('hanzi', 'like', "%$s%")->orWhere('pinyin', 'like', "%$s%")
                ->orWhere('meaning_id', 'like', "%$s%")->orWhere('meaning_en', 'like', "%$s%"));
        }
        $filter = $r->get('filter', 'all');
        if ($filter === 'belum') $q->whereDoesntHave('testResults', $hafal);
        if ($filter === 'sudah') $q->whereHas('testResults', $hafal);

        $words = $q->paginate(50)->withQueryString();

        $stars = VocabTestResult::where('user_id', $uid)->where('status', 'hafal')
            ->whereIn('vocab_word_id', $words->pluck('id'))
            ->selectRaw('vocab_word_id, count(*) c')->groupBy('vocab_word_id')->pluck('c', 'vocab_word_id');

        $total   = VocabWord::count();
        $learned = VocabTestResult::where('user_id', $uid)->where('status', 'hafal')
            ->distinct()->count('vocab_word_id');

        // status kemajuan dibanding Study Plan
        $state = 'none';
        if ($plan = VocabStudyPlan::where('user_id', $uid)->first()) {
            $days     = max(0, (int) $plan->start_date->diffInDays(today(), false));
            $expected = min($total, ($days + 1) * $plan->words_per_day);
            $state    = $learned >= $expected ? 'on_track' : 'behind';
        }
        $pct = $total ? $learned / $total * 100 : 0;

        return view('Siswa.vocab.list', [
            'title' => 'Belajar Vocab › Vocab List',
            'words' => $words, 'stars' => $stars, 'filter' => $filter,
            'total' => $total, 'learned' => $learned, 'state' => $state,
            'stage' => $pct < 25 ? 0 : ($pct < 50 ? 1 : ($pct < 75 ? 2 : 3)),
            'pct'   => $pct,
        ]);
    }

    /* ================================ TEST ================================ */
    public function test(string $type = 'reading')
    {
        abort_unless(in_array($type, self::TYPES), 404);

        $words = VocabWord::where('trap_only', false)->orderBy('order_number')->paginate(20);
        $results = VocabTestResult::where('user_id', auth()->id())->where('test_type', $type)
            ->whereIn('vocab_word_id', $words->pluck('id'))->get()->keyBy('vocab_word_id');

        return view('Siswa.vocab.test', [
            'title' => 'Belajar Vocab › Test',
            'type' => $type, 'words' => $words, 'results' => $results,
            'init' => $this->payload($type),
        ]);
    }

    public function answer(Request $r, string $type)
    {
        abort_unless(in_array($type, self::TYPES), 404);
        $d = $r->validate([
            'word_id' => 'required|exists:vocab_words,id',
            'answer' => 'nullable|string|max:255', 'answer_extra' => 'nullable|string|max:255',
            'ragu' => 'nullable|boolean',
        ]);

        $word   = VocabWord::where('trap_only', false)->findOrFail($d['word_id']);
        $status = VocabTestEvaluator::evaluate($type, $word, $d['answer'] ?? null, $d['answer_extra'] ?? null, (bool) ($d['ragu'] ?? false));
        $key    = ['user_id' => auth()->id(), 'vocab_word_id' => $word->id, 'test_type' => $type];

        if ($status === null) {
            VocabTestResult::where($key)->delete();
        } else {
            VocabTestResult::updateOrCreate($key, [
                'answer' => $d['answer'] ?? null, 'answer_extra' => $d['answer_extra'] ?? null,
                'is_ragu' => $status === 'ragu', 'status' => $status,
            ]);
        }

        return response()->json(['status' => $status ?? 'blank', 'label' => $this->label($status)] + $this->payload($type));
    }

    /** Refresh: ambil ulang kata ragu/salah (opsional diacak) */
    public function pending(Request $r, string $type)
    {
        abort_unless(in_array($type, self::TYPES), 404);
        return response()->json($this->payload($type, (bool) $r->shuffle));
    }

    /** Reset: hapus jawaban kata ragu & salah supaya bisa diulang sampai hafal */
    public function reset(string $type)
    {
        abort_unless(in_array($type, self::TYPES), 404);
        VocabTestResult::where('user_id', auth()->id())->where('test_type', $type)
            ->whereIn('status', ['ragu', 'salah'])->delete();
        return response()->json($this->payload($type));
    }

    private function label(?string $s): string
    {
        return ['hafal' => 'HAFAL', 'ragu' => 'RAGU', 'salah' => 'SALAH'][$s] ?? '-';
    }

    private function payload(string $type, bool $shuffle = false): array
    {
        $uid    = auth()->id();
        $counts = VocabTestResult::where('user_id', $uid)->where('test_type', $type)
            ->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        $rows = VocabTestResult::with('word')->where('user_id', $uid)->where('test_type', $type)
            ->whereIn('status', ['ragu', 'salah'])->latest('updated_at')->get();
        if ($shuffle) $rows = $rows->shuffle();

        $pending = $rows->values()->map(fn ($x) => [
            'prompt' => match ($type) {
                'reading'   => $x->word->hanzi,
                'listening' => '🔊 ' . $x->word->pinyin,
                default     => $x->word->meaning_id,
            },
            'answer' => collect([$x->answer, $x->answer_extra])->filter()->implode(' / ') ?: '-',
            'status' => $x->status, 'label' => $this->label($x->status),
        ]);

        return [
            'total'   => VocabWord::where('trap_only', false)->count(),
            'counts'  => ['hafal' => (int) ($counts['hafal'] ?? 0), 'ragu' => (int) ($counts['ragu'] ?? 0), 'salah' => (int) ($counts['salah'] ?? 0)],
            'pending' => $pending,
        ];
    }

    /* ============================ VOCAB TRAPS ============================ */
    public function traps(Request $r)
    {
        $uid = auth()->id();
        $s   = trim((string) $r->q);
        $get = fn ($type) => VocabTrap::with('word.examples')->where('type', $type)
            ->when($s, fn ($q) => $q->whereHas('word', fn ($w) => $w->where('hanzi', 'like', "%$s%")
                ->orWhere('pinyin', 'like', "%$s%")->orWhere('meaning_id', 'like', "%$s%")))
            ->orderBy('sort_order')->get();

        $understood = VocabTrapUnderstood::where('user_id', $uid)->pluck('vocab_word_id')->all();

        return view('Siswa.vocab.traps', [
            'title' => 'Belajar Vocab › Vocab Traps',
            'sameMeaning' => $get('same_meaning'), 'sameHanzi' => $get('same_hanzi'),
            'understood' => $understood, 'totalTrap' => VocabTrap::count(), 'done' => count($understood),
        ]);
    }

    public function understood(VocabWord $word)
    {
        $key = ['user_id' => auth()->id(), 'vocab_word_id' => $word->id];
        $row = VocabTrapUnderstood::where($key)->first();
        $row ? $row->delete() : VocabTrapUnderstood::create($key);

        return response()->json([
            'understood' => ! $row,
            'done' => VocabTrapUnderstood::where('user_id', auth()->id())->count(),
        ]);
    }
}
