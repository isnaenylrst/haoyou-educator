<?php

namespace App\Services;

use App\Models\PointLog;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PointService
{
    public const POINTS = ['kuning' => 10, 'merah' => 5];

    /** Maksimal siswa per jenis poin, per kelas, per tanggal sesi (1 Kuning + 1 Merah). */
    public const LIMIT_PER_TYPE = 1;

    /**
     * Poin yang sudah tercatat untuk sekelompok siswa (biasanya siswa satu kelas) pada tanggal tertentu.
     */
    public function takenLogs(iterable $studentIds, string $date)
    {
        return PointLog::with(['student', 'inputBy'])
            ->whereIn('student_id', collect($studentIds)->all())
            ->whereDate('session_date', $date)
            ->get();
    }

    /**
     * Beri poin ke satu siswa. Melempar DomainException kalau slot siswa itu di tanggal tersebut sudah terisi,
     * atau jatah jenis poin di kelasnya sudah terpakai.
     *
     * $classStudentIds = siswa aktif di kelas yang sama (untuk menghitung batas per kelas).
     */
    public function give(Student $student, string $date, string $type, int $userId, string $role, ?iterable $classStudentIds = null): void
    {
        $r = $this->giveBulk([$student->id], $date, $type, $userId, $role, null, $classStudentIds);

        if ($r['awarded'] === 0) {
            $existing = PointLog::with('inputBy')
                ->where('student_id', $student->id)
                ->whereDate('session_date', $date)
                ->first();

            throw new \DomainException($existing?->summary ?? 'Poin untuk tanggal ini sudah pernah diinput.');
        }
    }

    /**
     * Beri poin ke banyak siswa dengan jenis yang sama. Siswa yang sudah punya poin di tanggal itu dilewati.
     * Kalau $classStudentIds diisi, batas LIMIT_PER_TYPE per kelas per tanggal ikut dicek.
     */
    public function giveBulk(
        iterable $studentIds,
        string $date,
        string $type,
        int $userId,
        string $role,
        ?int $journalId = null,
        ?iterable $classStudentIds = null
    ): array {
        $ids    = collect($studentIds)->map(fn ($i) => (int) $i)->unique()->values();
        $points = self::POINTS[$type];

        $scope = $classStudentIds !== null
            ? collect($classStudentIds)->map(fn ($i) => (int) $i)->merge($ids)->unique()->values()
            : $ids;

        return DB::transaction(function () use ($ids, $scope, $date, $type, $points, $userId, $role, $journalId, $classStudentIds) {
            // kunci baris siswa (satu kelas) supaya admin & guru yang simpan bersamaan tidak dobel / melewati batas
            $locked   = Student::whereIn('id', $scope)->lockForUpdate()->get()->keyBy('id');
            $students = $ids->map(fn ($id) => $locked->get($id))->filter();

            $taken = PointLog::whereIn('student_id', $ids)
                ->whereDate('session_date', $date)
                ->pluck('student_id')
                ->map(fn ($i) => (int) $i)
                ->all();

            $newCount = $ids->reject(fn ($id) => in_array($id, $taken, true))->count();

            // Batas per kelas per tanggal
            if ($classStudentIds !== null && $newCount > 0) {
                $used = PointLog::with('student')
                    ->whereIn('student_id', $scope)
                    ->whereDate('session_date', $date)
                    ->where('type', $type)
                    ->get();

                $label = ucfirst($type);
                $tgl   = Carbon::parse($date)->format('d/m/Y');

                if ($used->count() >= self::LIMIT_PER_TYPE) {
                    $names = $used->map(fn ($l) => $l->student->name ?? '-')->implode(', ');

                    throw new \DomainException(
                        "Poin {$label} di kelas ini pada {$tgl} sudah diberikan kepada {$names}. "
                        . 'Maksimal ' . self::LIMIT_PER_TYPE . " siswa untuk poin {$label} per sesi."
                    );
                }

                if ($used->count() + $newCount > self::LIMIT_PER_TYPE) {
                    throw new \DomainException(
                        'Maksimal ' . self::LIMIT_PER_TYPE . " siswa untuk poin {$label} per sesi."
                    );
                }
            }

            $awarded = 0;
            $skipped = [];

            foreach ($students as $s) {
                if (in_array((int) $s->id, $taken, true)) {
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
}