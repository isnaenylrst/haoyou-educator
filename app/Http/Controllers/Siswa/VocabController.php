<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\ClassEnrollment;
use App\Models\Material;
use App\Models\VocabChapter;
use App\Models\VocabStudyPlan;
use App\Models\VocabTestResult;
use App\Models\VocabTrap;
use App\Models\VocabTrapUnderstood;
use App\Models\VocabWord;
use App\Models\VocabSetting;
use App\Services\VocabTestEvaluator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VocabController extends Controller
{
    private const TYPES = ['reading', 'listening', 'writing'];

    /**
     * Level (HSK 1/2/3...) milik siswa yang sedang login.
     * !!! SESUAIKAN baris ini dengan struktur project kamu !!!
     * Contoh lain: auth()->user()->student->program_level_id
     * Jika null => semua kata tampil (tidak difilter).
     */
       private function levelId(): ?int
    {
        return \App\Support\StudentLevel::id();
    }

    /** Daftar bab untuk level siswa + bab yang sedang dipilih (?bab=2). */
    private function chapterContext(Request $r): array
    {
        $chapters = VocabChapter::withCount('words')
            ->when($this->levelId(), fn ($q, $lvl) => $q->whereIn('program_level_id', VocabWord::equivalentLevelIds($lvl)))
            ->orderBy('number')->get();

        $chapter = $chapters->firstWhere('number', (int) $r->query('bab'));

        return [$chapters, $chapter?->number, $chapter?->id];
    }

    /* ============================ STUDY PLAN ============================ */
    public function plan()
    {
        return view('Siswa.vocab.plan', [
            'title'   => 'Belajar Vocab › Study Plan',
            'plan'    => VocabStudyPlan::where('user_id', auth()->id())->first(),
            'total'   => VocabWord::forLevel($this->levelId())->count(),
            'minutes' => [5 => '5 menit', 10 => '10 menit', 15 => '15 menit', 20 => '20 menit', 30 => '30 menit',
                          45 => '45 menit', 60 => '1 jam', 90 => '1,5 jam', 120 => '2 jam', 180 => '3 jam'],
        ]);
    }

    public function guide()
    {
        $uid   = auth()->id();
        $hafal = VocabTestResult::where('user_id', $uid)->where('status', 'hafal');

        return view('Siswa.vocab.guide', [
            'title'   => 'Belajar Vocab › Panduan',
            'videoId' => VocabSetting::youtubeId(VocabSetting::read('guide_video')),
            'progress' => [
                'plan'    => VocabStudyPlan::where('user_id', $uid)->exists(),
                'tested'  => VocabTestResult::where('user_id', $uid)->count(),
                'learned' => (clone $hafal)->distinct()->count('vocab_word_id'),
                'stars'   => (clone $hafal)->count(),
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
        $uid   = auth()->id();
        $level = $this->levelId();
        $hafal = fn ($q) => $q->where('user_id', $uid)->where('status', 'hafal');

        [$chapters, $bab, $chapterId] = $this->chapterContext($r);

        $q = VocabWord::forLevel($level)->with('examples')->orderBy('order_number');
        if ($chapterId) $q->where('vocab_chapter_id', $chapterId);

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

        // progres dihitung per LEVEL (bukan per bab)
        $total   = VocabWord::forLevel($level)->where('trap_only', false)->count();
        $learned = VocabTestResult::where('user_id', $uid)->where('status', 'hafal')
            ->whereHas('word', fn ($w) => $w->forLevel($level))
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
            'chapters' => $chapters, 'bab' => $bab,
        ]);
    }

    /* ================================ TEST ================================ */
    public function test(Request $r, string $type = 'reading')
    {
        abort_unless(in_array($type, self::TYPES), 404);

        [$chapters, $bab, $chapterId] = $this->chapterContext($r);

        $words = VocabWord::forLevel($this->levelId())->where('trap_only', false)
            ->when($chapterId, fn ($q) => $q->where('vocab_chapter_id', $chapterId))
            ->orderBy('order_number')->paginate(20)->withQueryString();

        $results = VocabTestResult::where('user_id', auth()->id())->where('test_type', $type)
            ->whereIn('vocab_word_id', $words->pluck('id'))->get()->keyBy('vocab_word_id');

        return view('Siswa.vocab.test', [
            'title' => 'Belajar Vocab › Test',
            'type' => $type, 'words' => $words, 'results' => $results,
            'init' => $this->payload($type),
            'chapters' => $chapters, 'bab' => $bab,
        ]);
    }

    public function answer(Request $r, string $type)
    {
        abort_unless(in_array($type, self::TYPES), 404);
        $d = $r->validate([
            'word_id' => 'required|exists:vocab_words,id',
            'pinyin'  => 'nullable|string|max:255',
            'tone'    => 'nullable|string|max:50',
            'hanzi'   => 'nullable|string|max:255',
            'meaning' => 'nullable|string|max:255',
            'ragu'    => 'nullable|boolean',
        ]);

        // siswa hanya boleh menjawab kata dari level-nya sendiri
        $word = VocabWord::forLevel($this->levelId())->where('trap_only', false)->findOrFail($d['word_id']);

        // hanya ambil kolom yang memang dipakai tes ini
        $ans = [];
        foreach (VocabTestEvaluator::FIELDS[$type] as $f) $ans[$f] = $d[$f] ?? null;

        $status = VocabTestEvaluator::evaluate($type, $word, $ans, (bool) ($d['ragu'] ?? false));
        $key    = ['user_id' => auth()->id(), 'vocab_word_id' => $word->id, 'test_type' => $type];

        if ($status === null) {
            VocabTestResult::where($key)->delete();
        } else {
            VocabTestResult::updateOrCreate($key, [
                'answer_pinyin'  => $ans['pinyin']  ?? null,
                'answer_tone'    => $ans['tone']    ?? null,
                'answer_hanzi'   => $ans['hanzi']   ?? null,
                'answer_meaning' => $ans['meaning'] ?? null,
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
            ->whereIn('status', ['ragu', 'salah'])
            ->whereHas('word', fn ($w) => $w->forLevel($this->levelId()))
            ->delete();
        return response()->json($this->payload($type));
    }

    private function label(?string $s): string
    {
        return ['hafal' => 'HAFAL', 'ragu' => 'RAGU', 'salah' => 'SALAH'][$s] ?? '-';
    }

    private function payload(string $type, bool $shuffle = false): array
    {
        $uid   = auth()->id();
        $level = $this->levelId();
        $inLevel = fn ($w) => $w->forLevel($level);

        $counts = VocabTestResult::where('user_id', $uid)->where('test_type', $type)
            ->whereHas('word', $inLevel)
            ->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        $rows = VocabTestResult::with('word')->where('user_id', $uid)->where('test_type', $type)
            ->whereHas('word', $inLevel)
            ->whereIn('status', ['ragu', 'salah'])->latest('updated_at')->get();
        if ($shuffle) $rows = $rows->shuffle();

        $fields = VocabTestEvaluator::FIELDS[$type];

        $pending = $rows->values()->map(fn ($x) => [
            'prompt' => match ($type) {
                'reading'   => $x->word->hanzi,
                'listening' => '🔊 ' . $x->word->hanzi,
                default     => $x->word->meaning_id,
            },
            'answer' => collect($fields)->map(fn ($f) => $x->{'answer_' . $f})->filter()->implode(' / ') ?: '-',
            'status' => $x->status, 'label' => $this->label($x->status),
        ]);

        return [
            'total'   => VocabWord::forLevel($level)->where('trap_only', false)->count(),
            'counts'  => ['hafal' => (int) ($counts['hafal'] ?? 0), 'ragu' => (int) ($counts['ragu'] ?? 0), 'salah' => (int) ($counts['salah'] ?? 0)],
            'pending' => $pending,
        ];
    }

    /* ============================ VOCAB TRAPS ============================ */
    public function traps(Request $r)
    {
        $uid   = auth()->id();
        $level = $this->levelId();
        $s     = trim((string) $r->q);

        $get = fn ($type) => VocabTrap::with('word.examples')->where('type', $type)
            ->whereHas('word', fn ($w) => $w->forLevel($level))
            ->when($s, fn ($q) => $q->whereHas('word', fn ($w) => $w->where(fn ($x) => $x
                ->where('hanzi', 'like', "%$s%")->orWhere('pinyin', 'like', "%$s%")->orWhere('meaning_id', 'like', "%$s%"))))
            ->orderBy('sort_order')->get();

        $understood = VocabTrapUnderstood::where('user_id', $uid)->pluck('vocab_word_id')->all();

        return view('Siswa.vocab.traps', [
            'title' => 'Belajar Vocab › Vocab Traps',
            'sameMeaning' => $get('same_meaning'), 'sameHanzi' => $get('same_hanzi'),
            'understood' => $understood,
            'totalTrap' => VocabTrap::whereHas('word', fn ($w) => $w->forLevel($level))->count(),
            'done' => count($understood),
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

    /* ============================ MATERI HUAHUA ============================ */
    /**
     * Mengambil level dan program/kategori dari enrollment aktif siswa.
     */
    private function getStudentEnrollmentScope(): array
    {
        $student = auth()->user()?->student;
        if (! $student) {
            return [[], []];
        }

        $enrollments = ClassEnrollment::with(['class.level.category', 'class.programPackage.category'])
            ->where('student_id', $student->id)
            ->where('status', 'Active')
            ->get();

        $levelIds = $enrollments->flatMap(fn ($e) => [
            $e->class?->level_id,
            $e->class?->programPackage?->level_id,
            $e->programPackage?->level_id,
        ])->filter()->unique()->values()->all();

        $categoryIds = $enrollments->flatMap(fn ($e) => [
            $e->class?->level?->category_id,
            $e->class?->programPackage?->category_id,
            $e->programPackage?->category_id,
        ])->filter()->unique()->values()->all();

        return [$levelIds, $categoryIds];
    }


    /**
     * Memeriksa otorisasi materi Huahua untuk siswa yang sedang login.
     */
     private function authorizeHuahuaMaterial(Material $material): void
    {
        abort_unless($material->audience === 'Huahua', 404);

        [$levelIds, $categoryIds] = $this->getStudentEnrollmentScope();

        $isAllowed = false;
        // Materi tanpa program dan tanpa level (Umum) boleh terlihat jika audience Huahua
        if (is_null($material->program_id) && is_null($material->level_id)) {
            $isAllowed = true;
        } elseif ($material->level_id && in_array($material->level_id, $levelIds, true)) {
            $isAllowed = true;
        } elseif (is_null($material->level_id) && $material->program_id && in_array($material->program_id, $categoryIds, true)) {
            $isAllowed = true;
        }

        abort_unless($isAllowed, 403, 'Materi Huahua ini bukan untuk program atau level kelasmu.');
        abort_unless($material->file_exists, 404, 'File materi tidak ditemukan.');
    }

    public function materi(Request $r)
    {
        [$levelIds, $categoryIds] = $this->getStudentEnrollmentScope();

        $query = Material::forHuahua()
            ->with(['program', 'level'])
            ->withCount('huahuaWords')
            ->where(function ($q) use ($levelIds, $categoryIds) {
                if (! empty($levelIds)) {
                    $q->whereIn('level_id', $levelIds);
                }
                if (! empty($categoryIds)) {
                    $q->orWhere(function ($sub) use ($categoryIds) {
                        $sub->whereIn('program_id', $categoryIds)
                            ->whereNull('level_id');
                    });
                }
                // Materi umum (tanpa program & tanpa level)
                $q->orWhere(function ($sub) {
                    $sub->whereNull('program_id')->whereNull('level_id');
                });
            });

        $materials = $query
            ->orderByRaw('program_id IS NULL DESC, program_id ASC')
            ->orderBy('meeting_number')
            ->get();

        $groups = $materials->groupBy(fn ($m) => $m->program_name);

        return view('Siswa.vocab.materi', [
            'title'  => 'Belajar Vocab › Materi Huahua',
            'groups' => $groups,
        ]);
    }

    public function materiLihat(Material $material)
    {
        $this->authorizeHuahuaMaterial($material);

        if (! $material->is_pdf) {
            return $this->materiUnduh($material);
        }

        return Storage::disk('public')->response(
            $material->material_file_path,
            null,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function materiUnduh(Material $material)
    {
        $this->authorizeHuahuaMaterial($material);

        $name = (\Illuminate\Support\Str::slug($material->title) ?: 'materi-' . $material->id) . '.' . $material->file_extension;

        return Storage::disk('public')->download($material->material_file_path, $name);
    }
}

