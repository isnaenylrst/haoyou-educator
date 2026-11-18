<?php

namespace App\Services;

use App\Models\PointLog;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class PointService
{
    public const POINTS = ['kuning' => 10, 'merah' => 5];

    public static function typeFromScore(int $score): string
    {
        return $score === 10 ? 'kuning' : 'merah';
    }

    /** Beri poin ke satu siswa. Melempar DomainException kalau slot tanggal itu sudah terisi. */
    public function give(Student $student, string $date, string $type, int $userId, string $role): void
    {
        $r = $this->giveBulk([$student->id], $date, $type, $userId, $role);

        if ($r['awarded'] === 0) {
            $existing = PointLog::with('inputBy')
                ->where('student_id', $student->id)
                ->whereDate('session_date', $date)
                ->first();

            throw new \DomainException($existing?->summary ?? 'Poin untuk tanggal ini sudah pernah diinput.');
        }
    }

    /** Beri poin ke banyak siswa. Yang sudah punya poin di tanggal itu dilewati. */
    public function giveBulk(iterable $studentIds, string $date, string $type, int $userId, string $role, ?int $journalId = null): array
    {
        $ids    = collect($studentIds)->unique()->values();
        $points = self::POINTS[$type];

        return DB::transaction(function () use ($ids, $date, $type, $points, $userId, $role, $journalId) {
            // kunci baris siswa supaya admin & guru yang simpan bersamaan tidak dobel
            $students = Student::whereIn('id', $ids)->lockForUpdate()->get();

            $taken = PointLog::whereIn('student_id', $ids)
                ->whereDate('session_date', $date)
                ->pluck('student_id')->all();

            $awarded = 0;
            $skipped = [];

            foreach ($students as $s) {
                if (in_array($s->id, $taken, true)) {
                    $skipped[] = $s->name;
                    continue;
                }

                $before = (int) $s->points;
                $s->increment('points', $points);

                PointLog::create([
                    'student_id'          => $s->id,
                    'session_date'        => $date,
                    'type'                => $type,
                    'points'              => $points,
                    'points_before'       => $before,
                    'points_after'        => $before + $points,
                    'input_by'            => $userId,
                    'input_role'          => $role,
                    'teaching_journal_id' => $journalId,
                ]);
                $awarded++;
            }

            return ['awarded' => $awarded, 'skipped' => $skipped];
        });
    }

    /** True kalau SEMUA siswa sudah punya poin di tanggal itu (radio guru dikunci). */
    public function allHavePoint(iterable $studentIds, string $date): bool
    {
        $ids = collect($studentIds)->unique();

        if ($ids->isEmpty()) {
            return false;
        }

        return PointLog::whereIn('student_id', $ids)
            ->whereDate('session_date', $date)
            ->distinct()->count('student_id') >= $ids->count();
    }
}