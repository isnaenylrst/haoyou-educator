<?php

namespace App\Support;

use App\Models\GrammarAnswerLog;
use App\Models\GrammarProgress;
use App\Models\ProgramLevel;
use App\Models\SelfStudyCertificate;
use App\Models\VocabTestResult;
use App\Models\VocabWord;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SelfStudyReport
{
    /** Ringkasan progres, bintang, dan nilai siswa. */
    public static function summary(int $uid): array
    {
        $level = StudentLevel::id();

        $vocabTotal   = VocabWord::forLevel($level)->where('trap_only', false)->count();
        $vocabLearned = VocabTestResult::where('user_id', $uid)->where('status', 'hafal')
            ->whereHas('word', fn ($w) => $w->forLevel($level))
            ->distinct()->count('vocab_word_id');

        $grammarTotal   = count(GrammarData::list());
        $grammarLearned = GrammarProgress::where('user_id', $uid)->where('kind', 'list')->count();

        $vocabMax   = $vocabTotal * 3;
        $vocabStars = VocabTestResult::where('user_id', $uid)
            ->where('status', 'hafal')
            ->whereHas('word', fn ($w) => $w->forLevel($level)->where('trap_only', false))
            ->count();

        $grammar = GrammarStars::forUser($uid);
        $score   = SelfStudyScore::make($vocabStars, $vocabMax, $grammar['total'], $grammar['max']);

        return [
            'vocab_total'     => $vocabTotal,
            'vocab_learned'   => $vocabLearned,
            'grammar_total'   => $grammarTotal,
            'grammar_learned' => $grammarLearned,
            'vocab_stars'     => $vocabStars,
            'vocab_max'       => $vocabMax,
            'grammar_stars'   => $grammar['total'],
            'grammar_max'     => $grammar['max'],
            'score'           => $score,
        ];
    }

    /** Boleh menerbitkan sertifikat baru? (lulus dan nilai lebih tinggi dari sertifikat sebelumnya) */
    public static function eligibility(int $uid, array $score): array
    {
        if (! $score['lulus']) {
            return [
                'ok'     => false,
                'reason' => 'Capai nilai minimal ' . $score['passing'] . ' untuk membuka sertifikat. Nilaimu sekarang ' . $score['total'] . '.',
            ];
        }

        $best = SelfStudyCertificate::where('user_id', $uid)->max('total_score');
        if ($best !== null && $score['total'] <= (int) $best) {
            return [
                'ok'     => false,
                'reason' => 'Nilaimu belum naik dari sertifikat terakhir (' . $best . '). Belajar lagi untuk menerbitkan sertifikat baru.',
            ];
        }

        return ['ok' => true, 'reason' => null];
    }

    /** Terbitkan sertifikat (snapshot nilai saat ini). */
    public static function issue($user): array
    {
        $uid     = (int) $user->id;
        $summary = self::summary($uid);
        $score   = $summary['score'];

        $check = self::eligibility($uid, $score);
        if (! $check['ok']) {
            return ['ok' => false, 'message' => $check['reason'], 'certificate' => null];
        }

        // periode: dari jawaban pertama sampai jawaban terakhir
        $firsts = collect([
            VocabTestResult::where('user_id', $uid)->min('created_at'),
            GrammarAnswerLog::where('user_id', $uid)->min('answered_at'),
            GrammarProgress::where('user_id', $uid)->where('kind', 'test')->min('created_at'),
        ])->filter()->map(fn ($d) => Carbon::parse($d))->sort();

        $lasts = collect([
            VocabTestResult::where('user_id', $uid)->max('updated_at'),
            GrammarAnswerLog::where('user_id', $uid)->max('answered_at'),
            GrammarProgress::where('user_id', $uid)->where('kind', 'test')->max('updated_at'),
        ])->filter()->map(fn ($d) => Carbon::parse($d))->sort();

        $start = $firsts->first() ?? now();
        $end   = $lasts->last() ?? now();

        $name = DB::table('students')->where('user_id', $uid)->value('name') ?: ($user->name ?: ($user->username ?: '-'));

        $levelId   = StudentLevel::id();
        $levelName = null;
        try {
            $levelName = $levelId ? ProgramLevel::find($levelId)?->level_name : null;
        } catch (\Throwable $e) {
            // abaikan, nama level opsional
        }

        $year = (int) now()->format('Y');

        for ($try = 0; $try < 3; $try++) {
            try {
                $cert = DB::transaction(function () use ($uid, $year, $name, $levelId, $levelName, $summary, $score, $start, $end) {
                    $seq = (int) SelfStudyCertificate::where('year', $year)->max('sequence') + 1;

                    return SelfStudyCertificate::create([
                        'user_id'            => $uid,
                        'year'               => $year,
                        'sequence'           => $seq,
                        'certificate_number' => sprintf('HY/MANDIRI/%d/%04d', $year, $seq),
                        'student_name'       => $name,
                        'program_level_id'   => $levelId,
                        'level_name'         => $levelName,
                        'vocab_stars'        => $summary['vocab_stars'],
                        'vocab_max'          => $summary['vocab_max'],
                        'grammar_stars'      => $summary['grammar_stars'],
                        'grammar_max'        => $summary['grammar_max'],
                        'vocab_pct'          => $score['vocab_pct'],
                        'grammar_pct'        => $score['grammar_pct'],
                        'total_score'        => $score['total'],
                        'predicate'          => $score['predikat'],
                        'weight_vocab'       => $score['weight_vocab'],
                        'weight_grammar'     => $score['weight_grammar'],
                        'passing_score'      => $score['passing'],
                        'period_start'       => $start->toDateString(),
                        'period_end'         => $end->toDateString(),
                        'issued_at'          => now(),
                    ]);
                });

                return ['ok' => true, 'message' => 'Sertifikat berhasil dibuat.', 'certificate' => $cert];
            } catch (QueryException $e) {
                // nomor urut bentrok (dua permintaan bersamaan): coba lagi
                if ($try === 2) {
                    throw $e;
                }
            }
        }

        return ['ok' => false, 'message' => 'Sertifikat belum bisa dibuat. Coba lagi.', 'certificate' => null];
    }
}