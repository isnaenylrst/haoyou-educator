<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Membangkitkan pertemuan bertanggal (class_sessions) dari pola mingguan (class_schedules).
 */
class ClassSessionService
{
    public function __construct(
        private ClassScheduleService $schedules,
        private SessionFeeService $fees,
    ) {
    }

    /**
     * Dipakai saat kelas baru dibuat (mis. convert siswa private):
     * bangkitkan $totalMeetings pertemuan mulai start_date kelas, lalu isi end_date
     * dengan tanggal pertemuan terakhir kalau belum terisi.
     */
    public function generateForNewClass(ClassModel $class, int $totalMeetings): Collection
    {
        $sessions = $this->generate($class, $class->start_date, $totalMeetings);

        if ($sessions->isNotEmpty() && ! $class->end_date) {
            $class->update(['end_date' => $sessions->last()->session_date->toDateString()]);
        }

        return $sessions;
    }

    /**
     * Bangkitkan $count pertemuan mulai tanggal $from.
     * Pola yang dipakai adalah pola yang berlaku pada tanggal masing-masing pertemuan
     * (memperhatikan effective_from / effective_until).
     *
     * @param  int|null  $startNumber  nomor pertemuan pertama; null = lanjutkan dari nomor terbesar yang ada
     */
    public function generate(ClassModel $class, Carbon|string $from, int $count, ?int $startNumber = null): Collection
    {
        // Satu transaksi: kalau satu tanggal bentrok, tidak ada pertemuan setengah jadi.
        return DB::transaction(fn () => $this->doGenerate($class, $from, $count, $startNumber));
    }

    private function doGenerate(ClassModel $class, Carbon|string $from, int $count, ?int $startNumber): Collection
    {
        $created = collect();

        if ($count < 1) {
            return $created;
        }

        $patterns = $class->allSchedules()->orderBy('start_time')->get();

        if ($patterns->isEmpty()) {
            return $created;
        }

        $number = $startNumber ?? ((int) ClassSession::where('class_id', $class->id)->max('session_number') + 1);
        $date   = Carbon::parse($from)->startOfDay();
        $limit  = $date->copy()->addYears(2); // pengaman supaya loop tidak tak terbatas
        $location = $class->is_home_visit
            ? 'Home Visit'
            : ($class->delivery_mode === 'Online' ? 'Online' : 'Studio');

        while ($created->count() < $count && $date->lte($limit)) {
            $dayName = ClassScheduleService::DAYS[$date->dayOfWeekIso - 1];

            $todays = $patterns->filter(
                fn (ClassSchedule $p) => $p->day === $dayName && $this->isActiveOn($p, $date)
            );

            foreach ($todays as $pattern) {
                if ($created->count() >= $count) {
                    break;
                }

                if ($class->teacher_id) {
                    $this->schedules->assertTeacherFreeOn(
                        (int) $class->teacher_id,
                        $date,
                        $pattern->start_time,
                        $pattern->end_time,
                        null,
                        $class->id,
                    );
                }

                $this->schedules->assertRoomFreeOn(
                    $location === 'Studio' ? $pattern->room : null,
                    $date,
                    $pattern->start_time,
                    $pattern->end_time,
                );

                $session = ClassSession::create([
                    'class_id'          => $class->id,
                    'class_schedule_id' => $pattern->id,
                    'kind'              => 'Regular',
                    'session_number'    => $number++,
                    'session_date'      => $date->toDateString(),
                    'start_time'        => $pattern->start_time,
                    'end_time'          => $pattern->end_time,
                    'room'              => $location === 'Home Visit' ? null : $pattern->room,
                    'teacher_id'        => $class->teacher_id,
                    'location_type'     => $location,
                    'status'            => 'Scheduled',
                ]);

                // Home Visit: biaya Rp50.000 per pertemuan tercatat otomatis
                $this->fees->syncFor($session);

                $created->push($session);
            }

            $date->addDay();
        }

        return $created;
    }

    /**
     * Tambah pertemuan (kelas pengganti) untuk satu siswa. Satu pertemuan, di luar hitungan paket,
     * berbayar (SessionFeeService::FEE_REPLACEMENT). Guru boleh berbeda dari guru kelas asal.
     * Tanpa kelas induk & tanpa pola mingguan. Mengubah jadwal kelas reguler bukan di sini,
     * melainkan lewat ClassSessionRescheduleService.
     */
    public function createReplacement(array $data): ClassSession
    {
        $enrollment = ClassEnrollment::findOrFail($data['class_enrollment_id']);

        $date  = Carbon::parse($data['session_date']);
        $start = Carbon::parse($data['start_time'])->format('H:i:s');
        $end   = Carbon::parse($data['end_time'])->format('H:i:s');
        $type  = $data['location_type'];

        if ($date->lt(today())) {
            throw ValidationException::withMessages(['session_date' => 'Tanggal tidak boleh di masa lalu.']);
        }
        if ($end <= $start) {
            throw ValidationException::withMessages(['end_time' => 'Jam selesai harus setelah jam mulai.']);
        }

        $room = match ($type) {
            'Studio' => $data['room'] ?? null,
            'Online' => 'Online',
            default  => null, // Home Visit
        };

        if ($type === 'Studio' && (! $room || $room === 'Online')) {
            throw ValidationException::withMessages(['room' => 'Pilih ruangan untuk pertemuan di studio.']);
        }

        return DB::transaction(function () use ($enrollment, $data, $date, $start, $end, $type, $room) {
            $this->schedules->assertTeacherFreeOn((int) $data['teacher_id'], $date, $start, $end);

            if ($type === 'Studio') {
                $this->schedules->assertRoomFreeOn($room, $date, $start, $end);
            }

            $session = ClassSession::create([
                'class_id'            => null,
                'class_enrollment_id' => $enrollment->id,
                'class_schedule_id'   => null,
                'kind'                => 'Replacement',
                'session_number'      => null,
                'session_date'        => $date->toDateString(),
                'start_time'          => $start,
                'end_time'            => $end,
                'room'                => $room,
                'teacher_id'          => $data['teacher_id'],
                'location_type'       => $type,
                'status'              => 'Scheduled',
            ]);

            $this->fees->syncFor($session);

            return $session;
        });
    }

    /** Tandai pertemuan selesai (status Completed). Setelah itu pertemuan terkunci. */
    public function complete(ClassSession $session): ClassSession
    {
        if (! $session->canBeCompleted()) {
            throw ValidationException::withMessages([
                'session' => 'Hanya pertemuan Scheduled yang tanggalnya sudah tiba dan belum punya jurnal yang bisa ditandai selesai.',
            ]);
        }

        $session->update(['status' => 'Completed']);

        return $session;
    }

    private function isActiveOn(ClassSchedule $pattern, Carbon $date): bool
    {
        $startsOk = ! $pattern->effective_from || $pattern->effective_from->lte($date);
        $endsOk   = ! $pattern->effective_until || $pattern->effective_until->gte($date);

        return $startsOk && $endsOk;
    }
}