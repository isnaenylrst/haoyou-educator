<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\TeacherLeave;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ubah jadwal pertemuan: satu pertemuan saja, atau pertemuan ini dan seterusnya.
 * Hanya hari/jam/ruang yang berubah. Ganti guru lewat izin guru (teacher_leaves).
 */
class ClassSessionRescheduleService
{
    public function __construct(private ClassScheduleService $schedules)
    {
    }

    /* ---------------------------------------------------------- */
    /*  Satu pertemuan saja                                        */
    /* ---------------------------------------------------------- */

    public function rescheduleOne(ClassSession $session, array $data): ClassSession
    {
        $this->assertEditable($session);
        $slot = $this->slotFrom($session, $data);

        return DB::transaction(function () use ($session, $slot, $data) {
            $this->assertNotPast($slot['date']);
            $this->assertNoOpenLeave($session);
            $this->assertSlotFree($session, $slot, [$session->id]);

            // Di luar jam tersedia guru: boleh, tapi admin harus mencentang konfirmasi.
            if ($session->teacher_id
                && empty($data['confirm_outside_availability'])
                && ! $this->schedules->isTeacherAvailable(
                    (int) $session->teacher_id,
                    ClassScheduleService::DAYS[Carbon::parse($slot['date'])->dayOfWeekIso - 1],
                    $slot['start'],
                    $slot['end'],
                )) {
                throw ValidationException::withMessages([
                    'availability' => 'Jadwal baru di luar jam tersedia guru. Centang konfirmasi kalau guru sudah setuju.',
                ]);
            }

            $this->move($session, $slot, $data['reason'] ?? null);
            $this->refreshClassEndDate($session->class_id);

            return $session->fresh();
        });
    }

    /* ---------------------------------------------------------- */
    /*  Pertemuan ini dan seterusnya                               */
    /* ---------------------------------------------------------- */

    /** @return int jumlah pertemuan yang dipindah */
    public function rescheduleFollowing(ClassSession $session, array $data): int
    {
        $this->assertEditable($session);

        $old = $session->schedule;

        if ($session->kind !== 'Regular' || ! $old) {
            throw ValidationException::withMessages([
                'scope' => 'Kelas pengganti / pertemuan manual hanya bisa diubah untuk satu pertemuan.',
            ]);
        }

        if (! $old->isCurrent()) {
            throw ValidationException::withMessages([
                'scope' => 'Pola jadwal pertemuan ini sudah ditutup, tidak bisa diubah "seterusnya".',
            ]);
        }

        $slot      = $this->slotFrom($session, $data);
        $newDay    = ClassScheduleService::DAYS[Carbon::parse($slot['date'])->dayOfWeekIso - 1];
        $targetDow = Carbon::parse($slot['date'])->dayOfWeekIso - 1; // 0 = Senin
        $teacherId = $session->teacher_id ?? $session->class?->teacher_id;
        $row       = [
            'day'        => $newDay,
            'start_time' => substr($slot['start'], 0, 5),
            'end_time'   => substr($slot['end'], 0, 5),
        ];

        // Pola mingguan baru harus muat di jam tersedia guru & tidak bentrok antar kelas / antar pola
        if ($teacherId) {
            $this->schedules->assertTeacherAvailable((int) $teacherId, [$row]);
            $this->schedules->assertTeacherFree((int) $teacherId, [$row], $session->class_id);
        }

        $sameClassClash = ClassSchedule::where('class_id', $old->class_id)
            ->whereNull('effective_until')
            ->where('id', '!=', $old->id)
            ->where('day', $newDay)
            ->where('start_time', '<', $slot['end'])
            ->where('end_time', '>', $slot['start'])
            ->exists();

        if ($sameClassClash) {
            throw ValidationException::withMessages([
                'session_date' => "Bentrok dengan pola jadwal lain di kelas yang sama pada hari {$newDay}.",
            ]);
        }

        return DB::transaction(function () use ($session, $old, $slot, $newDay, $targetDow, $data) {

            // Pertemuan yang ikut berpindah: yang diklik + berikutnya (nomor lebih besar)
            // dari pola yang sama, belum terkunci, dan tidak pernah dipindah satu-satu.
            $sessions = ClassSession::with('schedule')
                ->where('class_schedule_id', $old->id)
                ->where('kind', 'Regular')
                ->where('status', 'Scheduled')
                ->where('session_number', '>=', $session->session_number)
                ->orderBy('session_number')
                ->get()
                ->filter(fn (ClassSession $s) => $s->id === $session->id
                    || (! $s->isLocked() && ! $s->deviatesFromPattern()))
                ->values();

            $ids  = $sessions->pluck('id')->all();
            $plan = [];

            $prev = ClassSession::where('class_id', $session->class_id)
                ->where('session_number', '<', $session->session_number)
                ->where('status', '!=', 'Cancelled')
                ->max('session_date');
            $prev = $prev ? Carbon::parse($prev) : null;

            // 1) Susun & validasi semua tanggal baru dulu (belum ada yang disimpan)
            foreach ($sessions as $s) {
                $newDate = $s->id === $session->id
                    ? Carbon::parse($slot['date'])
                    : $s->session_date->copy()->startOfWeek(Carbon::MONDAY)->addDays($targetDow);

                if ($prev && $newDate->lte($prev)) {
                    throw ValidationException::withMessages([
                        'session_date' => 'Urutan pertemuan jadi tidak valid: tanggal baru bertabrakan dengan pertemuan sebelum/sesudahnya.',
                    ]);
                }

                $this->assertNotPast($newDate->toDateString());
                $this->assertNoOpenLeave($s);
                $this->assertSlotFree($s, ['date' => $newDate->toDateString()] + $slot, $ids);

                $plan[] = [$s, $newDate->toDateString()];
                $prev   = $newDate;
            }

            // 2) Pola mingguan: ubah di tempat kalau belum punya riwayat, kalau sudah tutup & buat versi baru
            $patternDate = ($session->original_date ?? $session->session_date)->toDateString();

            $hasHistory = $old->teachingJournals()->exists()
                || $old->sessions()->whereDate('session_date', '<', $patternDate)->exists();

            $pattern = [
                'day'        => $newDay,
                'start_time' => $slot['start'],
                'end_time'   => $slot['end'],
                'room'       => $slot['room'] ?? $old->room, // Home Visit: ruang pola dipertahankan
            ];

            if ($hasHistory) {
                $old->closeOn(Carbon::parse($patternDate)->subDay()->toDateString());

                $new = ClassSchedule::create($pattern + [
                    'class_id'        => $old->class_id,
                    'effective_from'  => min($patternDate, $slot['date']),
                    'effective_until' => null,
                ]);
            } else {
                $old->update($pattern);
                $new = $old;
            }

            // 3) Pindahkan pertemuan & arahkan ke pola yang berlaku
            foreach ($plan as [$s, $date]) {
                $this->move($s, ['date' => $date] + $slot, $data['reason'] ?? null);
            }

            if ($new->id !== $old->id) {
                ClassSession::whereIn('id', $ids)->update(['class_schedule_id' => $new->id]);
            }

            $this->refreshClassEndDate($session->class_id);

            return count($plan);
        });
    }

    /* ---------------------------------------------------------- */
    /*  Helper                                                     */
    /* ---------------------------------------------------------- */

    private function slotFrom(ClassSession $s, array $data): array
    {
        $minutes = abs((int) Carbon::parse($s->start_time)->diffInMinutes(Carbon::parse($s->end_time)));
        $start   = Carbon::parse($data['start_time']);

        $room = $s->location_type === 'Studio' ? ($data['room'] ?? $s->room) : $s->room;

        if ($s->location_type === 'Studio' && (empty($room) || $room === 'Online')) {
            throw ValidationException::withMessages(['room' => 'Pilih ruangan untuk pertemuan offline.']);
        }

        return [
            'date'  => Carbon::parse($data['session_date'])->toDateString(),
            'start' => $start->format('H:i:s'),
            'end'   => $start->copy()->addMinutes($minutes)->format('H:i:s'),
            'room'  => $room,
        ];
    }

    private function move(ClassSession $s, array $slot, ?string $reason): void
    {
        $s->update([
            'original_date' => $s->original_date ?? $s->session_date,
            'session_date'  => $slot['date'],
            'start_time'    => $slot['start'],
            'end_time'      => $slot['end'],
            'room'          => $slot['room'],
            'change_reason' => $reason,
        ]);
    }

    private function assertEditable(ClassSession $s): void
    {
        if (! $s->canBeRescheduled()) {
            throw ValidationException::withMessages([
                'session' => 'Hanya pertemuan Scheduled yang belum punya jurnal dan belum lewat yang bisa diubah jadwalnya.',
            ]);
        }
    }

    private function assertNotPast(string $date): void
    {
        if (Carbon::parse($date)->lt(today())) {
            throw ValidationException::withMessages(['session_date' => "Tanggal {$date} sudah lewat."]);
        }
    }

    /** Izin guru yang masih berjalan pada pertemuan ini menahan perpindahan (leave_date merujuk tanggal lama). */
    private function assertNoOpenLeave(ClassSession $s): void
    {
        if (! $s->class_schedule_id) {
            return;
        }

        $dates = array_filter([
            $s->session_date?->toDateString(),
            $s->original_date?->toDateString(),
        ]);

        $has = TeacherLeave::where('class_schedule_id', $s->class_schedule_id)
            ->whereIn('leave_date', $dates)
            ->whereIn('status', ['Pending', 'Approved'])
            ->exists();

        if ($has) {
            throw ValidationException::withMessages([
                'session' => 'Pertemuan tanggal ' . $s->session_date->toDateString()
                    . ' punya izin guru yang masih berjalan. Selesaikan/batalkan izinnya dulu.',
            ]);
        }
    }

    /** Guru & ruang bebas pada slot baru (pertemuan yang ikut dipindah diabaikan). */
    private function assertSlotFree(ClassSession $s, array $slot, array $ignoreIds): void
    {
        if ($s->teacher_id) {
            $this->schedules->assertTeacherFreeOn(
                (int) $s->teacher_id, $slot['date'], $slot['start'], $slot['end'], $ignoreIds
            );
        }

        if ($s->location_type === 'Studio') {
            $this->schedules->assertRoomFreeOn(
                $slot['room'], $slot['date'], $slot['start'], $slot['end'], $ignoreIds
            );
        }
    }

    private function refreshClassEndDate(?int $classId): void
    {
        if (! $classId) {
            return;
        }

        $last = ClassSession::where('class_id', $classId)
            ->where('kind', 'Regular')
            ->where('status', '!=', 'Cancelled')
            ->max('session_date');

        if ($last) {
            ClassModel::whereKey($classId)->update(['end_date' => $last]);
        }
    }
}