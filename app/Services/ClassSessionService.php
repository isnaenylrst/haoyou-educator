<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassSessionService
{
    public function __construct(
        private ClassScheduleService $schedules,
        private ExtraFeeService $fees,
    ) {
    }

    /**
     * Generate pertemuan untuk kelas baru.
     */
    public function generateForNewClass(
        ClassModel $class,
        int $totalMeetings
    ): Collection {
        $sessions = $this->generate(
            $class,
            $class->start_date,
            $totalMeetings
        );

        if ($sessions->isNotEmpty() && ! $class->end_date) {
            $class->update([
                'end_date' => $sessions->last()->session_date->toDateString(),
            ]);
        }

        return $sessions;
    }

    /**
     * Generate pertemuan untuk kelas lama yang belum memiliki sesi.
     */
    public function generateForExistingClass(ClassModel $class): Collection
    {
        if (
            ! $class->start_date
            || ClassSession::where('class_id', $class->id)->exists()
        ) {
            return collect();
        }

        $total = (int) (
            $class->programPackage?->total_meetings
            ?? $class->privatePackage?->total_meetings
            ?? 0
        );

        return $total > 0
            ? $this->generateForNewClass($class, $total)
            : collect();
    }

    /**
     * Generate sejumlah pertemuan berdasarkan pola jadwal mingguan.
     */
    public function generate(
        ClassModel $class,
        Carbon|string $from,
        int $count,
        ?int $startNumber = null
    ): Collection {
        return DB::transaction(
            fn () => $this->doGenerate(
                $class,
                $from,
                $count,
                $startNumber
            )
        );
    }

    private function doGenerate(
        ClassModel $class,
        Carbon|string $from,
        int $count,
        ?int $startNumber
    ): Collection {
        $created = collect();

        if ($count < 1) {
            return $created;
        }

        $patterns = $class->allSchedules()
            ->orderBy('start_time')
            ->get();

        if ($patterns->isEmpty()) {
            return $created;
        }

        $number = $startNumber
            ?? (
                (int) ClassSession::where('class_id', $class->id)
                    ->max('session_number') + 1
            );

        $date = Carbon::parse($from)->startOfDay();
        $limit = $date->copy()->addYears(2);

        // Jadwal rutin tidak otomatis menjadi Home Visit.
        $location = $class->delivery_mode === 'Online'
            ? 'Online'
            : 'Studio';

        while ($created->count() < $count && $date->lte($limit)) {
            $dayName = ClassScheduleService::DAYS[
                $date->dayOfWeekIso - 1
            ];

            $todays = $patterns->filter(
                fn (ClassSchedule $pattern) =>
                    $pattern->day === $dayName
                    && $this->isActiveOn($pattern, $date)
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
                    'class_id' => $class->id,
                    'class_schedule_id' => $pattern->id,
                    'kind' => 'Regular',
                    'session_number' => $number++,
                    'session_date' => $date->toDateString(),
                    'start_time' => $pattern->start_time,
                    'end_time' => $pattern->end_time,
                    'room' => $pattern->room,
                    'teacher_id' => $class->teacher_id,
                    'location_type' => $location,
                    'status' => 'Scheduled',
                ]);

                // Tidak membuat pembayaran otomatis saat generate jadwal.
                $created->push($session);
            }

            $date->addDay();
        }

        return $created;
    }

    /**
     * Membuat sesi pengganti untuk satu enrollment.
     *
     * Jika lokasi Home Visit, buat dua pembayaran:
     * 1. Replacement.
     * 2. Home Visit.
     *
     * Data pembayaran yang diterima:
     * payment_date, payment_method, payment_proof_path, amount (opsional).
     */
    public function createReplacement(array $data): ClassSession
    {
        $date = Carbon::parse($data['session_date'])->startOfDay();
        $start = Carbon::parse($data['start_time'])->format('H:i:s');
        $end = Carbon::parse($data['end_time'])->format('H:i:s');
        $type = $data['location_type'];

        if ($date->lt(today())) {
            throw ValidationException::withMessages([
                'session_date' => 'Tanggal tidak boleh di masa lalu.',
            ]);
        }

        if ($end <= $start) {
            throw ValidationException::withMessages([
                'end_time' => 'Jam selesai harus setelah jam mulai.',
            ]);
        }

        if (! in_array($type, ['Studio', 'Online', 'Home Visit'], true)) {
            throw ValidationException::withMessages([
                'location_type' => 'Tipe lokasi pertemuan tidak valid.',
            ]);
        }

        $room = match ($type) {
            'Studio' => $data['room'] ?? null,
            'Online' => 'Online',
            default => null,
        };

        if ($type === 'Studio' && (! $room || $room === 'Online')) {
            throw ValidationException::withMessages([
                'room' => 'Pilih ruangan untuk pertemuan di studio.',
            ]);
        }

        $pay = [
            'payment_date' => $data['payment_date'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_proof_path' => $data['payment_proof_path'] ?? null,
        ];

        if (array_key_exists('amount', $data)) {
            $pay['amount'] = $data['amount'];
        }

        return DB::transaction(function () use (
            $data,
            $date,
            $start,
            $end,
            $type,
            $room,
            $pay
        ) {
            $enrollment = ClassEnrollment::query()
                ->whereKey($data['class_enrollment_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($enrollment->status !== 'Active') {
                throw ValidationException::withMessages([
                    'class_enrollment_id' =>
                        'Sesi pengganti hanya dapat dibuat untuk enrollment berstatus Active.',
                ]);
            }

            $this->schedules->assertTeacherAvailable(
                (int) $data['teacher_id'],
                [[
                    'day' => ClassScheduleService::DAYS[
                        $date->dayOfWeekIso - 1
                    ],
                    'start_time' => substr($start, 0, 5),
                    'end_time' => substr($end, 0, 5),
                ]]
            );

            $this->schedules->assertTeacherFreeOn(
                (int) $data['teacher_id'],
                $date,
                $start,
                $end
            );

            if ($type === 'Studio') {
                $this->schedules->assertRoomFreeOn(
                    $room,
                    $date,
                    $start,
                    $end
                );
            }

            $session = ClassSession::create([
                'class_id' => null,
                'class_enrollment_id' => $enrollment->id,
                'class_schedule_id' => null,
                'kind' => 'Replacement',
                'session_number' => null,
                'session_date' => $date->toDateString(),
                'start_time' => $start,
                'end_time' => $end,
                'room' => $room,
                'teacher_id' => $data['teacher_id'],
                'location_type' => $type,
                'status' => 'Scheduled',
            ]);

            // Biaya kelas pengganti selalu dicatat.
            $this->fees->record(
                $enrollment,
                Payment::TYPE_REPLACEMENT,
                $session,
                $pay
            );

            // Home Visit menambahkan pembayaran kedua.
            if ($type === 'Home Visit') {
                $this->fees->record(
                    $enrollment,
                    Payment::TYPE_HOME_VISIT,
                    $session,
                    $pay
                );
            }

            return $session->fresh();
        });
    }

    /**
     * Mengubah sesi menjadi Home Visit sekaligus mencatat pembayarannya.
     */
    public function makeHomeVisit(
        ClassSession $session,
        ClassEnrollment $payer,
        array $pay
    ): ClassSession {
        return DB::transaction(function () use ($session, $payer, $pay) {
            $lockedSession = ClassSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedSession->canBecomeHomeVisit()) {
                throw ValidationException::withMessages([
                    'session' =>
                        'Sesi ini tidak dapat diubah menjadi Home Visit.',
                ]);
            }

            // Sesi pengganti harus terhubung ke enrollment pembayar.
            if ($lockedSession->class_enrollment_id !== null) {
                if (
                    (int) $lockedSession->class_enrollment_id
                    !== (int) $payer->id
                ) {
                    throw ValidationException::withMessages([
                        'session' =>
                            'Sesi pengganti ini bukan milik enrollment yang dipilih.',
                    ]);
                }
            } else {
                // Sesi reguler harus berasal dari kelas enrollment pembayar.
                if (
                    $lockedSession->class_id === null
                    || $payer->class_id === null
                    || (int) $lockedSession->class_id
                    !== (int) $payer->class_id
                ) {
                    throw ValidationException::withMessages([
                        'session' =>
                            'Sesi reguler ini tidak sesuai dengan kelas enrollment yang dipilih.',
                    ]);
                }
            }

            $lockedSession->update([
                'location_type' => 'Home Visit',
                'room' => null,
            ]);

            // Jika pencatatan pembayaran gagal, perubahan lokasi ikut dibatalkan.
            $this->fees->record(
                $payer,
                Payment::TYPE_HOME_VISIT,
                $lockedSession,
                $pay
            );

            return $lockedSession->fresh();
        });
    }

    /**
     * Mengubah beberapa sesi menjadi Home Visit dalam satu transaksi.
     *
     * @param array<int> $sessionIds
     */
    public function makeHomeVisitMany(
        array $sessionIds,
        ClassEnrollment $payer,
        array $pay
    ): Collection {
        if (empty($sessionIds)) {
            throw ValidationException::withMessages([
                'session_ids' => 'Pilih minimal satu pertemuan.',
            ]);
        }

        return DB::transaction(function () use ($sessionIds, $payer, $pay) {
            $updated = collect();

            foreach (array_unique($sessionIds) as $sessionId) {
                $session = ClassSession::query()
                    ->whereKey($sessionId)
                    ->firstOrFail();

                $updated->push(
                    $this->makeHomeVisit($session, $payer, $pay)
                );
            }

            return $updated;
        });
    }

    /**
     * Menandai sesi selesai.
     */
    public function complete(ClassSession $session): ClassSession
    {
        if (! $session->canBeCompleted()) {
            throw ValidationException::withMessages([
                'session' =>
                    'Hanya pertemuan Scheduled yang tanggalnya sudah tiba dan belum punya jurnal yang bisa ditandai selesai.',
            ]);
        }

        $session->update([
            'status' => 'Completed',
        ]);

        return $session;
    }

    private function isActiveOn(
        ClassSchedule $pattern,
        Carbon $date
    ): bool {
        $startsOk = ! $pattern->effective_from
            || $pattern->effective_from->lte($date);

        $endsOk = ! $pattern->effective_until
            || $pattern->effective_until->gte($date);

        return $startsOk && $endsOk;
    }
}