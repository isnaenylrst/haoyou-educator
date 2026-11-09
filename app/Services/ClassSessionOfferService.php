<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\ClassSessionTeacherOffer;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Kelas pengganti: admin tidak mengisi guru langsung. Pertemuan dibuat tanpa guru (teacher_id kosong),
 * diajukan ke beberapa guru, dan class_sessions.teacher_id baru terisi setelah salah satu guru menerima
 * (siapa cepat dia dapat); pengajuan guru lain ikut ditutup. Mirip ClassOfferService, tapi per pertemuan.
 */
class ClassSessionOfferService
{
    public function __construct(private ClassScheduleService $schedules)
    {
    }

    /**
     * Semua guru aktif beserta statusnya pada tanggal & jam tertentu.
     *  available : jam tersedia guru mencakup waktu ini
     *  free      : guru tidak bentrok dengan pertemuan lain
     *  reason    : alasan kalau tidak cocok (null kalau cocok)
     */
    public function candidates(string $date, string $start, string $end, int|array|null $ignoreSessionId = null): Collection
    {
        $day = ClassScheduleService::DAYS[Carbon::parse($date)->dayOfWeekIso - 1];

        return Teacher::where('status', 'Active')->orderBy('name')->get(['id', 'name'])
            ->map(function (Teacher $t) use ($date, $start, $end, $day, $ignoreSessionId) {
                $free = true;

                try {
                    $this->schedules->assertTeacherFreeOn($t->id, $date, $start, $end, $ignoreSessionId);
                } catch (ValidationException) {
                    $free = false;
                }

                $available = $this->schedules->isTeacherAvailable($t->id, $day, $start, $end);

                return [
                    'id'        => $t->id,
                    'name'      => $t->name,
                    'available' => $available,
                    'free'      => $free,
                    'reason'    => ! $free ? 'Sudah mengajar di jam ini' : (! $available ? 'Di luar jam tersedia' : null),
                ];
            })
            ->values();
    }

    /**
     * Admin mengajukan pertemuan.
     *  selected = guru pilihan admin, matching = semua guru yang cocok, all = semua guru aktif.
     * Guru yang masih menunggu jawaban tidak diajukan ulang. Mengembalikan nama guru yang baru diajukan.
     */
    public function offer(ClassSession $session, string $mode, array $teacherIds = []): Collection
    {
        $this->assertOpen($session);

        $active = Teacher::where('status', 'Active')->pluck('id')->map(fn ($i) => (int) $i);

        $ids = match ($mode) {
            'matching' => $this->candidates(
                    $session->session_date->toDateString(), $session->start_time, $session->end_time, $session->id
                )
                ->filter(fn ($c) => $c['available'] && $c['free'])
                ->pluck('id')->map(fn ($i) => (int) $i),
            'all'      => $active,
            default    => collect($teacherIds)->map(fn ($i) => (int) $i)->intersect($active),
        };

        $pending = ClassSessionTeacherOffer::where('class_session_id', $session->id)->where('status', 'Pending')
            ->pluck('teacher_id')->map(fn ($i) => (int) $i);

        $ids = $ids->unique()->diff($pending)->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'offer' => match ($mode) {
                    'matching' => 'Belum ada guru yang cocok di jam ini, atau semuanya sudah diajukan. Pilih "Semua guru" atau pilih guru satu per satu.',
                    'selected' => 'Pilih minimal satu guru yang belum diajukan.',
                    default    => 'Tidak ada guru baru untuk diajukan.',
                },
            ]);
        }

        foreach ($ids as $id) {
            ClassSessionTeacherOffer::updateOrCreate(
                ['class_session_id' => $session->id, 'teacher_id' => $id],
                ['status' => 'Pending', 'response_note' => null, 'responded_at' => null]
            );
        }

        return Teacher::whereIn('id', $ids)->orderBy('name')->pluck('name');
    }

    /** Guru menerima: guru pertemuan terisi, pengajuan guru lain ditutup. Siapa cepat dia dapat. */
    public function accept(ClassSession $session, Teacher $teacher): void
    {
        DB::transaction(function () use ($session, $teacher) {
            // Kunci baris supaya dua guru yang menerima bersamaan tidak sama-sama berhasil.
            $locked = ClassSession::lockForUpdate()->findOrFail($session->id);
            $tid    = (int) $teacher->id;

            $offer = ClassSessionTeacherOffer::where('class_session_id', $locked->id)
                ->where('teacher_id', $tid)->where('status', 'Pending')->lockForUpdate()->first();

            if (! $offer || ! $locked->isAwaitingTeacher()) {
                throw ValidationException::withMessages(['session' => 'Tawaran ini sudah tidak berlaku.']);
            }

            if ($locked->session_date->lt(today())) {
                throw ValidationException::withMessages(['session' => 'Tanggal pertemuan ini sudah lewat.']);
            }

            $this->schedules->assertTeacherFreeOn(
                $tid, $locked->session_date, $locked->start_time, $locked->end_time, $locked->id
            );

            $locked->update(['teacher_id' => $tid]);

            $offer->update(['status' => 'Accepted', 'responded_at' => now()]);

            ClassSessionTeacherOffer::where('class_session_id', $locked->id)->where('id', '!=', $offer->id)
                ->where('status', 'Pending')->update(['status' => 'Closed', 'responded_at' => now()]);
        });
    }

    /** Guru menolak: hanya tawaran guru itu yang berubah, pertemuan tetap terbuka untuk guru lain. */
    public function reject(ClassSession $session, Teacher $teacher, string $note): void
    {
        $n = ClassSessionTeacherOffer::where('class_session_id', $session->id)->where('teacher_id', $teacher->id)
            ->where('status', 'Pending')
            ->update(['status' => 'Rejected', 'response_note' => $note, 'responded_at' => now()]);

        if (! $n) {
            throw ValidationException::withMessages(['session' => 'Tawaran ini sudah tidak berlaku.']);
        }
    }

    /**
     * Admin membatalkan pertemuan yang masih menunggu guru. Pembayaran tidak dihapus
     * (pengembalian dana ditangani manual), tawaran yang terbuka ditutup.
     */
    public function cancel(ClassSession $session): void
    {
        DB::transaction(function () use ($session) {
            $locked = ClassSession::lockForUpdate()->findOrFail($session->id);

            if (! $locked->isAwaitingTeacher()) {
                throw ValidationException::withMessages([
                    'session' => 'Hanya pertemuan yang masih menunggu guru yang bisa dibatalkan.',
                ]);
            }

            $locked->update(['status' => 'Cancelled', 'change_reason' => 'Dibatalkan admin sebelum ada guru yang menerima']);

            $this->closeOffers($locked);
        });
    }

    public function closeOffers(ClassSession $session): void
    {
        ClassSessionTeacherOffer::where('class_session_id', $session->id)->where('status', 'Pending')
            ->update(['status' => 'Closed', 'responded_at' => now()]);
    }

    /** Pertemuan hanya bisa diajukan selama masih menunggu guru dan tanggalnya belum lewat. */
    private function assertOpen(ClassSession $session): void
    {
        if (! $session->isAwaitingTeacher()) {
            throw ValidationException::withMessages([
                'offer' => 'Pertemuan ini tidak sedang menunggu guru (sudah punya guru atau dibatalkan).',
            ]);
        }

        if ($session->session_date->lt(today())) {
            throw ValidationException::withMessages(['offer' => 'Tanggal pertemuan sudah lewat.']);
        }
    }
}