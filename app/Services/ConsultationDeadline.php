<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\TeachingJournal;
use Illuminate\Support\Carbon;

/**
 * Aturan Konsultasi Materi Baru:
 *   deadline konsultasi = tanggal mengajar pertama kelas - 14 hari (H-2 minggu).
 */
class ConsultationDeadline
{
    public const LEAD_DAYS = 14;

    private const ISO = [
        'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4,
        'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7,
    ];

    /**
     * Tanggal mengajar pertama: hari jadwal pertama yang jatuh pada / setelah tanggal mulai kelas.
     * Jika kelas belum punya jadwal, dipakai tanggal mulai kelas.
     */
    public static function firstSession(ClassModel $class): ?Carbon
    {
        if (!$class->start_date) {
            return null;
        }

        $start = Carbon::parse($class->start_date)->startOfDay();

        $days = $class->schedules
            ->pluck('day')
            ->map(fn ($d) => self::ISO[$d] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($days->isEmpty()) {
            return $start;
        }

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);

            if ($days->contains($date->dayOfWeekIso)) {
                return $date;
            }
        }

        return $start;
    }

    /** Batas akhir konsultasi materi baru (H-14 sebelum mengajar pertama). */
    public static function deadlineFor(ClassModel $class): ?Carbon
    {
        return self::firstSession($class)?->copy()->subDays(self::LEAD_DAYS);
    }

    /** Apakah guru ini pernah mengajar (kelas berjalan) pada level tersebut? */
    public static function teacherHasTaught(int $teacherId, ?int $levelId): bool
    {
        if (!$levelId) {
            return false;
        }

        return TeachingJournal::query()
            ->where('teacher_id', $teacherId)
            ->where('class_status', 'Conducted')
            ->whereHas('class', fn ($q) => $q->where('level_id', $levelId))
            ->exists();
    }
}