<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\SelfStudyReport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Monitoring progres Belajar Mandiri (Vocab + Grammar) untuk Kurikulum dan Owner.
 * Hanya membaca data; tidak mengubah apa pun.
 */
class SelfStudyMonitoringController extends Controller
{
    private const PASS_SCORE    = 60;
    private const CACHE_KEY     = 'self_study_monitor_rows';
    private const CACHE_MINUTES = 10;

    /* ============================ REKAP ============================ */
    public function index(Request $r)
    {
        $all = $this->allRows($r->boolean('refresh'));

        $stats = [
            'total'    => $all->count(),
            'belajar'  => $all->where('status', 'belajar')->count(),
            'lulus'    => $all->where('status', 'lulus')->count(),
            'inactive' => $all->filter(fn ($x) => $x['status'] !== 'belum'
                && (! $x['last_at'] || now()->diffInDays($x['last_at']) >= 7))->count(),
        ];
        $levels = $all->pluck('level')->filter()->unique()->sort()->values();

        $rows = $all;
        if ($s = trim((string) $r->q)) {
            $rows = $rows->filter(fn ($x) => stripos($x['name'] . ' ' . $x['username'], $s) !== false);
        }
        if ($r->filled('level'))  $rows = $rows->where('level', $r->level);
        if ($r->filled('status')) $rows = $rows->where('status', $r->status);

        $sortMap = ['name' => 'name', 'vocab' => 'vocab_stars', 'grammar' => 'grammar_stars', 'score' => 'score', 'last' => 'last_at'];
        $sort    = array_key_exists($r->sort, $sortMap) ? $r->sort : 'name';
        $dir     = $r->dir === 'desc' ? 'desc' : 'asc';
        $key     = $sortMap[$sort];
        $numeric = in_array($key, ['vocab_stars', 'grammar_stars', 'score'], true);
        $rows    = $rows->sortBy(fn ($x) => $x[$key] ?? ($numeric ? -1 : ''), SORT_NATURAL | SORT_FLAG_CASE, $dir === 'desc')->values();

        if ($r->boolean('export')) {
            return $this->exportCsv($rows);
        }

        $per   = 25;
        $page  = LengthAwarePaginator::resolveCurrentPage();
        $paged = new LengthAwarePaginator(
            $rows->forPage($page, $per)->values(), $rows->count(), $per, $page,
            ['path' => $r->url(), 'query' => $r->query()]
        );

        return view('kurikulum.belajar-mandiri.index', [
            'rows' => $paged, 'stats' => $stats, 'levels' => $levels,
            'sort' => $sort, 'dir' => $dir, 'passScore' => self::PASS_SCORE,
        ]);
    }

    /* ============================ DETAIL SISWA ============================ */
    public function show(Student $student)
    {
        $student->load(['user', 'currentLevel']);
        $uid = $student->user_id;

        $summary = $this->summaryFor($student);

        $grammarLogs = DB::table('grammar_answer_logs as l')
            ->leftJoin('grammar_questions as q', 'q.id', '=', 'l.question_no')
            ->where('l.user_id', $uid)
            ->orderByDesc('l.answered_at')->limit(100)
            ->get(['l.answered_at', 'l.question_no', 'q.soal', 'l.status', 'l.attempt_no']);

        $vocabTrouble = DB::table('vocab_test_results as r')
            ->join('vocab_words as w', 'w.id', '=', 'r.vocab_word_id')
            ->where('r.user_id', $uid)->whereIn('r.status', ['ragu', 'salah'])
            ->orderByDesc('r.updated_at')->limit(100)
            ->get(['w.hanzi', 'w.pinyin', 'w.meaning_id', 'r.test_type', 'r.status']);

        $vocabLogs = DB::table('vocab_answer_logs as l')
            ->join('vocab_words as w', 'w.id', '=', 'l.vocab_word_id')
            ->where('l.user_id', $uid)
            ->orderByDesc('l.answered_at')->limit(50)
            ->get(['l.answered_at', 'w.hanzi', 'w.pinyin', 'l.test_type', 'l.status', 'l.attempt_no']);

        return view('kurikulum.belajar-mandiri.show', compact('student', 'summary', 'grammarLogs', 'vocabTrouble', 'vocabLogs') + [
            'score'    => $this->scoreOf($summary),
            'predikat' => data_get($summary, 'score.predikat'),
        ]);
    }

    /* ============================ ANALISIS SOAL ============================ */
    public function analysis(Request $r)
    {
        $min = max(1, (int) $r->query('min', 3));

        $grammar = DB::table('grammar_answer_logs as l')
            ->join('grammar_questions as q', 'q.id', '=', 'l.question_no')
            ->selectRaw("q.id, q.soal, COUNT(*) AS dijawab, SUM(l.status = 'salah') AS salah,
                         ROUND(100 * SUM(l.status = 'salah') / COUNT(*)) AS persen")
            ->groupBy('q.id', 'q.soal')
            ->havingRaw('COUNT(*) >= ?', [$min])
            ->orderByDesc('persen')->orderByDesc('salah')->limit(20)->get();

        $vocab = DB::table('vocab_answer_logs as l')
            ->join('vocab_words as w', 'w.id', '=', 'l.vocab_word_id')
            ->selectRaw("w.id, w.hanzi, w.pinyin, w.meaning_id, l.test_type, COUNT(*) AS dijawab,
                         SUM(l.status = 'salah') AS salah, ROUND(100 * SUM(l.status = 'salah') / COUNT(*)) AS persen")
            ->groupBy('w.id', 'w.hanzi', 'w.pinyin', 'w.meaning_id', 'l.test_type')
            ->havingRaw('COUNT(*) >= ?', [$min])
            ->orderByDesc('persen')->orderByDesc('salah')->limit(20)->get();

        return view('kurikulum.belajar-mandiri.analisis', compact('grammar', 'vocab', 'min'));
    }

    /* ============================ HELPER ============================ */
   
private function allRows(bool $refresh): Collection
{
    if ($refresh) {
        Cache::forget(self::CACHE_KEY);
    }

    // Periksa cache terlebih dahulu
    $cached = Cache::get(self::CACHE_KEY);

    // Gunakan cache hanya jika benar-benar Collection
    if ($cached instanceof Collection) {
        return $cached;
    }

    // Hapus cache lama atau cache dengan tipe yang salah
    Cache::forget(self::CACHE_KEY);

    // Bangun ulang data monitoring
    $rows = $this->buildRows();

    // Simpan hasil baru
    Cache::put(
        self::CACHE_KEY,
        $rows,
        now()->addMinutes(self::CACHE_MINUTES)
    );

    return $rows;
}


    private function buildRows(): Collection
    {
        $students = Student::with(['user', 'currentLevel'])->whereNotNull('user_id')->orderBy('name')->get();
        $ids      = $students->pluck('user_id');

        $lastGrammar = DB::table('grammar_answer_logs')->whereIn('user_id', $ids)
            ->selectRaw('user_id, MAX(answered_at) AS last_at')->groupBy('user_id')->pluck('last_at', 'user_id');
        $lastVocab = DB::table('vocab_test_results')->whereIn('user_id', $ids)
            ->selectRaw('user_id, MAX(updated_at) AS last_at')->groupBy('user_id')->pluck('last_at', 'user_id');

        // kolom tabel sertifikat belum terlihat: dicek dulu supaya tidak error
        $certs = collect();
        if (Schema::hasTable('self_study_certificates') && Schema::hasColumn('self_study_certificates', 'user_id')) {
            $dateCol = Schema::hasColumn('self_study_certificates', 'created_at') ? 'MAX(created_at)' : 'NULL';
            $certs = DB::table('self_study_certificates')->whereIn('user_id', $ids)
                ->selectRaw("user_id, COUNT(*) AS total, {$dateCol} AS last_at")->groupBy('user_id')->get()->keyBy('user_id');
        }

        $original = Auth::user();
        $rows = collect();
        try {
            foreach ($students as $st) {
                $summary = $this->summaryFor($st);
                $score   = $this->scoreOf($summary);
                $last    = collect([$lastGrammar[$st->user_id] ?? null, $lastVocab[$st->user_id] ?? null])->filter()->max();

                $vocabStars   = (int) data_get($summary, 'vocab_stars', 0);
                $grammarStars = (int) data_get($summary, 'grammar_stars', 0);
                $started      = $vocabStars > 0 || $grammarStars > 0 || $last;
                $status       = ! $started ? 'belum' : (($score ?? 0) >= self::PASS_SCORE ? 'lulus' : 'belajar');

                $rows->push([
                    'student_id'   => $st->id,
                    'name'         => $st->name ?: ($st->user->username ?? '-'),
                    'username'     => $st->user->username ?? '',
                    'level'        => $st->currentLevel->level_name ?? null,
                    'vocab_stars'  => $vocabStars,
                    'vocab_max'    => (int) data_get($summary, 'vocab_max', 0),
                    'grammar_stars'=> $grammarStars,
                    'grammar_max'  => (int) data_get($summary, 'grammar_max', 0),
                    'score'        => $score,
                    'predikat'     => data_get($summary, 'score.predikat'),
                    'status'       => $status,
                    'cert_count'   => (int) ($certs[$st->user_id]->total ?? 0),
                    'cert_last'    => $certs[$st->user_id]->last_at ?? null,
                    'last_at'      => $last,
                    'error'        => $summary === null,
                ]);
            }
        } finally {
            $original ? Auth::setUser($original) : Auth::forgetGuards();
        }

        return $rows;
    }

    /** SelfStudyReport membaca level dari akun yang login, jadi akun siswa dipasang sementara. */
    private function summaryFor(Student $student): ?array
    {
        $original = Auth::user();
        try {
            Auth::setUser($student->user);
            return SelfStudyReport::summary($student->user_id);
        } catch (\Throwable $e) {
            report($e);
            return null;
        } finally {
            $original ? Auth::setUser($original) : Auth::forgetGuards();
        }
    }

    private function scoreOf(?array $summary): ?float
    {
        $s = data_get($summary, 'score.total', data_get($summary, 'score'));
        return is_numeric($s) ? (float) $s : null;
    }

    private function exportCsv(Collection $rows)
    {
        $labels = ['belum' => 'Belum mulai', 'belajar' => 'Sedang belajar', 'lulus' => 'Lulus'];

        return response()->streamDownload(function () use ($rows, $labels) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Siswa', 'Username', 'Level', 'Bintang Vocab', 'Maks Vocab', 'Bintang Grammar', 'Maks Grammar',
                           'Nilai Total', 'Predikat', 'Status', 'Jumlah Sertifikat', 'Sertifikat Terakhir', 'Terakhir Belajar']);
            foreach ($rows as $x) {
                fputcsv($out, [$x['name'], $x['username'], $x['level'], $x['vocab_stars'], $x['vocab_max'],
                    $x['grammar_stars'], $x['grammar_max'], $x['score'], $x['predikat'], $labels[$x['status']],
                    $x['cert_count'], $x['cert_last'], $x['last_at']]);
            }
            fclose($out);
        }, 'monitoring-belajar-mandiri-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}