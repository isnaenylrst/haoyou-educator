<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\ClassSession;
use App\Models\ClassTeacherOffer;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin tidak mengisi guru kelas langsung. Kelas diajukan ke beberapa guru (kelas baru, atau mengganti
 * guru yang sudah ada); classes.teacher_id baru terisi/berganti setelah salah satu guru menerima
 * (siapa cepat dia dapat), dan pengajuan guru lain ditutup. Sampai itu, guru lama tetap mengajar.
 */
class ClassOfferService
{
    public function __construct(private ClassScheduleService $schedules)
    {
    }

    /**
     * Admin mengajukan kelas.
     *  selected = guru pilihan admin, matching = semua guru yang cocok, all = semua guru aktif.
     * Guru yang masih menunggu jawaban tidak diajukan ulang. Mengembalikan nama guru yang baru diajukan.
     */
    public function offer(ClassModel $class, string $mode, array $teacherIds = []): Collection
    {
        $class->loadMissing('schedules');
        $active = Teacher::where('status', 'Active')->pluck('id')->map(fn ($i) => (int) $i);

        $ids = match ($mode) {
            'matching' => TeacherAvailabilityService::matchingTeacherIds($class)
                ->map(fn ($i) => (int) $i)->intersect($active),
            'all'      => $active,
            default    => collect($teacherIds)->map(fn ($i) => (int) $i)->intersect($active),
        };

        // Guru yang sekarang mengajar kelas ini tidak ikut diajukan
        if ($class->teacher_id) {
            $ids = $ids->reject(fn ($i) => $i === (int) $class->teacher_id);
        }

        $pending = ClassTeacherOffer::where('class_id', $class->id)->where('status', 'Pending')
            ->pluck('teacher_id')->map(fn ($i) => (int) $i);

        $ids = $ids->unique()->diff($pending)->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'offer' => match ($mode) {
                    'matching' => 'Belum ada guru yang cocok, atau semuanya sudah diajukan. Pilih "Semua guru" atau centang guru satu per satu.',
                    'selected' => 'Centang minimal satu guru yang belum diajukan, atau pilih "Jangan ajukan dulu".',
                    default    => 'Tidak ada guru baru untuk diajukan.',
                },
            ]);
        }

        foreach ($ids as $id) {
            ClassTeacherOffer::updateOrCreate(
                ['class_id' => $class->id, 'teacher_id' => $id],
                ['status' => 'Pending', 'response_note' => null, 'responded_at' => null]
            );
        }

        return Teacher::whereIn('id', $ids)->orderBy('name')->pluck('name');
    }

    /** Guru menerima: guru kelas terisi/berganti, pertemuan mendatang ikut, pengajuan guru lain ditutup. */
    public function accept(ClassModel $class, Teacher $teacher): void
    {
        DB::transaction(function () use ($class, $teacher) {
            // Kunci baris supaya dua guru yang menerima bersamaan tidak sama-sama berhasil.
            $kelas = ClassModel::with('schedules')->lockForUpdate()->findOrFail($class->id);
            $tid   = (int) $teacher->id;

            $offer = ClassTeacherOffer::where('class_id', $kelas->id)
                ->where('teacher_id', $tid)->where('status', 'Pending')->lockForUpdate()->first();

            if (! $offer) {
                throw ValidationException::withMessages(['class' => 'Tawaran ini sudah tidak berlaku.']);
            }

            if ((int) $kelas->teacher_id === $tid) {
                $offer->update(['status' => 'Closed', 'responded_at' => now()]);
                throw ValidationException::withMessages(['class' => 'Anda sudah menjadi guru kelas ini.']);
            }

            if ($clash = TeacherAvailabilityService::conflictWith($tid, $kelas)) {
                throw ValidationException::withMessages([
                    'class' => "Kelas ini bentrok dengan jadwal kelas {$clash} yang sudah Anda terima.",
                ]);
            }

            $sessions = ClassSession::where('class_id', $kelas->id)
                ->where('kind', 'Regular')
                ->where('status', 'Scheduled')
                ->whereDate('session_date', '>=', today())
                ->orderBy('session_date')
                ->get()
                ->reject(fn (ClassSession $s) => $s->isLocked());

            foreach ($sessions as $s) {
                $this->schedules->assertTeacherFreeOn($tid, $s->session_date, $s->start_time, $s->end_time, $s->id);
            }

            $kelas->forceFill([
                'teacher_id'            => $tid,
                'teacher_response'      => 'Accepted',
                'teacher_response_note' => null,
                'teacher_responded_at'  => now(),
            ])->save();

            $offer->update(['status' => 'Accepted', 'responded_at' => now()]);

            ClassTeacherOffer::where('class_id', $kelas->id)->where('id', '!=', $offer->id)
                ->where('status', 'Pending')->update(['status' => 'Closed', 'responded_at' => now()]);

            foreach ($sessions as $s) {
                $s->update(['teacher_id' => $tid]);
            }
        });
    }

    /** Guru menolak tawaran: hanya tawaran guru itu yang berubah, kelas tetap terbuka untuk guru lain. */
    public function reject(ClassModel $class, Teacher $teacher, string $note): void
    {
        $n = ClassTeacherOffer::where('class_id', $class->id)->where('teacher_id', $teacher->id)
            ->where('status', 'Pending')
            ->update(['status' => 'Rejected', 'response_note' => $note, 'responded_at' => now()]);

        if (! $n) {
            throw ValidationException::withMessages(['class' => 'Tawaran ini sudah tidak berlaku.']);
        }
    }

    /** Dipanggil saat guru ditetapkan langsung (admin/Kurikulum): tawaran yang masih terbuka ditutup. */
    public function closeOffers(ClassModel $class): void
    {
        ClassTeacherOffer::where('class_id', $class->id)->where('status', 'Pending')
            ->update(['status' => 'Closed', 'responded_at' => now()]);
    }
}