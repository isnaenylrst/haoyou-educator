<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Material;
use App\Models\TeacherLeave;
use App\Models\TeachingJournal;
use App\Services\MissingJournalFinder;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SATU-SATUNYA seeder untuk pertemuan (class_sessions) DAN jurnal mengajar (teaching_journals).
 * Menggantikan TeachingJournalSeeder + ClassSessionSeeder yang lama (hapus TeachingJournalSeeder
 * dari DatabaseSeeder).
 *
 * Cerita datanya, untuk setiap kelas "Running" yang punya jadwal mingguan:
 *  1. Daftar pertemuan dibuat LENGKAP: pertemuan 1 s.d. total_meetings (dari paket), jatuh tepat
 *     di hari jadwal mingguan mulai dari start_date kelas.
 *  2. Pertemuan yang sudah lewat  -> status sesi Completed/Cancelled + 1 jurnal yang tertaut.
 *  3. Pertemuan yang belum lewat   -> status sesi Scheduled, belum ada jurnal.
 *  4. Cuti Approved: ada guru pengganti -> jurnal diisi guru pengganti (is_substitute);
 *     tanpa pengganti -> sesi Cancelled, tidak ada jurnal (tidak dihitung tertunggak).
 *  5. Sengaja ada data uji "jurnal tertunggak": MISSING_DEMO_SESSIONS pertemuan terakhir yang sudah
 *     lewat pada MISSING_DEMO_CLASSES kelas Running pertama dibiarkan TANPA jurnal.
 *  6. Sengaja ada pertemuan dibatalkan / dijadwal ulang pada pertemuan ke-2 di sebagian kelas.
 *
 * Jalankan SETELAH: ClassSeeder, ClassScheduleSeeder, MaterialSeeder, TeacherLeaveSeeder.
 * Jalankan SEBELUM: AttendanceSeeder dan ProgressReportSeeder.
 */
class ClassSessionSeeder extends Seeder
{
    /** Hapus dulu pertemuan reguler + jurnal + absensi lama supaya tidak dobel. */
    private const RESET_FIRST = true;

    /** Buat juga pertemuan masa depan (Scheduled, tanpa jurnal). Matikan kalau pertemuan masa depan dibuat oleh service. */
    private const SEED_FUTURE_SESSIONS = true;

    /** Berapa kelas Running pertama (urut id) yang punya jurnal tertunggak, dan berapa pertemuan terakhirnya. */
    private const MISSING_DEMO_CLASSES  = 2;
    private const MISSING_DEMO_SESSIONS = 2;

    public function run(): void
    {
        if (self::RESET_FIRST) {
            $this->purgeRegularSessions();
        }

        $now = now();

        $schedulesByClass = ClassSchedule::orderBy('start_time')->get()->groupBy('class_id');

        // Cuti Approved, dikunci per (jadwal, tanggal)
        $leaves = TeacherLeave::where('status', 'Approved')
            ->get()
            ->keyBy(fn ($leave) => $leave->class_schedule_id . '|' . $leave->leave_date->format('Y-m-d'));

        $classes = ClassModel::whereNotNull('teacher_id')
            ->whereIn('status', ['Running', 'Completed'])
            ->orderBy('id')
            ->get();

        $demoClassIds = $classes->where('status', 'Running')
            ->take(self::MISSING_DEMO_CLASSES)
            ->pluck('id')
            ->all();

        foreach ($classes->values() as $classIndex => $class) {
            $total     = (int) ($class->package()?->total_meetings ?? 0);
            $schedules = $schedulesByClass->get($class->id);

            if ($total < 1 || ! $schedules) {
                $this->command?->warn("Dilewati \"{$class->class_name}\": tidak punya paket/jadwal mingguan.");

                continue;
            }

            $meetings = $this->buildMeetings($class, $schedules, $total);

            foreach ($meetings as &$meeting) {
                $meeting['past'] = Carbon::parse($meeting['date']->toDateString() . ' ' . $meeting['schedule']->end_time)
                    ->lte($now);
            }
            unset($meeting);

            // Pertemuan terakhir yang sudah lewat, sengaja tanpa jurnal (data uji notifikasi jurnal tertunggak)
            $pastNumbers    = collect($meetings)->where('past', true)->pluck('number');
            $missingNumbers = in_array($class->id, $demoClassIds, true) && $pastNumbers->count() > self::MISSING_DEMO_SESSIONS
                ? $pastNumbers->take(-self::MISSING_DEMO_SESSIONS)->all()
                : [];

            $materials = $class->level_id
                ? Material::where('level_id', $class->level_id)->get()->keyBy('meeting_number')
                : collect();

            $stat = ['sessions' => 0, 'journals' => 0, 'missing' => 0, 'future' => 0];

            foreach ($meetings as $meeting) {
                if (! $meeting['past'] && ! self::SEED_FUTURE_SESSIONS) {
                    continue;
                }

                $schedule = $meeting['schedule'];
                $dateStr  = $meeting['date']->toDateString();
                $number   = $meeting['number'];

                $leave        = $leaves->get($schedule->id . '|' . $dateStr);
                $substituteId = $leave?->replacement_teacher_id;
                $teacherId    = $substituteId ?: $class->teacher_id;

                // Hasil pertemuan: null = belum ada jurnal
                $outcome       = null;
                $sessionStatus = 'Scheduled';
                $reason        = null;

                if ($leave && ! $substituteId) {
                    $sessionStatus = 'Cancelled';
                    $reason        = 'Guru cuti';
                } elseif ($meeting['past'] && ! in_array($number, $missingNumbers, true)) {
                    $outcome       = $this->outcomeFor($classIndex, $number);
                    $sessionStatus = $outcome === 'Conducted' ? 'Completed' : 'Cancelled';
                    $reason        = match ($outcome) {
                        'Rescheduled' => 'Dijadwalkan ulang',
                        'Cancelled'   => 'Dibatalkan',
                        default       => null,
                    };
                }

                $session = ClassSession::updateOrCreate(
                    ['class_id' => $class->id, 'session_number' => $number],
                    [
                        'class_schedule_id' => $schedule->id,
                        'kind'              => 'Regular',
                        'session_date'      => $dateStr,
                        'start_time'        => $schedule->start_time,
                        'end_time'          => $schedule->end_time,
                        'room'              => $schedule->room,
                        'teacher_id'        => $teacherId,
                        'location_type'     => $class->delivery_mode === 'Online' ? 'Online' : 'Studio',
                        'status'            => $sessionStatus,
                        'change_reason'     => $reason,
                    ]
                );

                $stat['sessions']++;

                if ($outcome) {
                    $this->createJournal($class, $session, $schedule, $number, $total, $outcome, $materials->get($number), $leave, $substituteId);
                    $stat['journals']++;
                } elseif ($meeting['past'] && ! ($leave && ! $substituteId)) {
                    $stat['missing']++;
                } elseif (! $meeting['past']) {
                    $stat['future']++;
                }
            }

            if ($class->status === 'Running' && collect($meetings)->every(fn ($m) => $m['past'])) {
                $this->command?->warn("\"{$class->class_name}\": semua pertemuan sudah lewat tetapi status kelas masih Running.");
            }

            $this->command?->info(sprintf(
                '%s (guru #%d): %d pertemuan, %d berjurnal, %d TANPA jurnal, %d mendatang',
                $class->class_name,
                $class->teacher_id,
                $stat['sessions'],
                $stat['journals'],
                $stat['missing'],
                $stat['future']
            ));
        }
    }

    /**
     * Pertemuan 1..$total: jatuh di hari jadwal mingguan, mulai dari start_date kelas.
     *
     * @return array<int, array{number:int, date:Carbon, schedule:ClassSchedule}>
     */
    private function buildMeetings(ClassModel $class, Collection $schedules, int $total): array
    {
        $byDay    = $schedules->groupBy('day');
        $meetings = [];
        $date     = $class->start_date->copy()->startOfDay();
        $guard    = 0;

        while (count($meetings) < $total && $guard++ < 730) {
            foreach ($byDay->get(MissingJournalFinder::DAYS[$date->dayOfWeekIso], collect()) as $schedule) {
                if ($schedule->effective_from && Carbon::parse($schedule->effective_from)->startOfDay()->gt($date)) {
                    continue;
                }
                if ($schedule->effective_until && Carbon::parse($schedule->effective_until)->startOfDay()->lt($date)) {
                    continue;
                }
                if (count($meetings) >= $total) {
                    break;
                }

                $meetings[] = [
                    'number'   => count($meetings) + 1,
                    'date'     => $date->copy(),
                    'schedule' => $schedule,
                ];
            }

            $date->addDay();
        }

        return $meetings;
    }

    /** Hasil pertemuan yang sudah lewat. Semua Conducted, kecuali pertemuan ke-2 di sebagian kelas. */
    private function outcomeFor(int $classIndex, int $number): string
    {
        if ($number === 2 && $classIndex % 4 === 1) {
            return 'Cancelled';
        }

        if ($number === 2 && $classIndex % 4 === 2) {
            return 'Rescheduled';
        }

        return 'Conducted';
    }

    private function createJournal(
        ClassModel $class,
        ClassSession $session,
        ClassSchedule $schedule,
        int $number,
        int $total,
        string $outcome,
        ?Material $material,
        ?TeacherLeave $leave,
        ?int $substituteId
    ): void {
        $conducted = $outcome === 'Conducted';

        [$activities, $results, $notes] = match ($outcome) {
            'Conducted' => [
                $material ? "Pertemuan ke-{$number}: {$material->title}." : "Pertemuan ke-{$number}.",
                'Siswa mengikuti pembelajaran dengan baik.',
                null,
            ],
            'Rescheduled' => [
                'Kelas dijadwal ulang, sesi ini belum terlaksana.',
                null,
                'Dipindah atas kesepakatan siswa',
            ],
            default => [
                'Kelas dibatalkan, sesi ini tidak terlaksana.',
                null,
                'Dibatalkan, tanpa pertemuan pengganti.',
            ],
        };

        // unguarded: tidak bergantung pada $fillable TeachingJournal
        TeachingJournal::unguarded(fn () => TeachingJournal::create([
            'teacher_id'            => $class->teacher_id,
            'class_id'              => $class->id,
            'class_schedule_id'     => $schedule->id,
            'class_session_id'      => $session->id,
            'session_date'          => $session->session_date,
            'material_id'           => $conducted ? $material?->id : null,
            'is_substitute'         => (bool) $substituteId,
            'is_last_meeting'       => $number === $total,
            'meeting_number'        => $number,
            'substitute_teacher_id' => $substituteId,
            'teacher_leave_id'      => $substituteId ? $leave?->id : null,
            'class_status'          => $outcome,
            'learning_activities'   => $activities,
            'results'               => $results,
            'notes'                 => $notes,
            'status'                => 'Pending',
        ]));
    }

    /**
     * Hapus pertemuan reguler lama + semua jurnal yang tertaut ke pertemuan itu (atau tanpa pertemuan)
     * + absensinya. Pertemuan "Replacement" (kelas pengganti) tidak disentuh.
     * Pakai DB::table agar tidak terpengaruh scope/soft delete model.
     */
    private function purgeRegularSessions(): void
    {
        $sessionIds = DB::table('class_sessions')->where('kind', 'Regular')->pluck('id');

        $journalIds = DB::table('teaching_journals')
            ->whereNull('class_session_id')
            ->orWhereIn('class_session_id', $sessionIds)
            ->pluck('id');

        DB::transaction(function () use ($sessionIds, $journalIds) {
            foreach ($journalIds->chunk(500) as $ids) {
                DB::table('attendances')->whereIn('teaching_journal_id', $ids)->delete();
                DB::table('teaching_journals')->whereIn('id', $ids)->delete();
            }

            foreach ($sessionIds->chunk(500) as $ids) {
                DB::table('class_sessions')->whereIn('id', $ids)->delete();
            }
        });
    }
}