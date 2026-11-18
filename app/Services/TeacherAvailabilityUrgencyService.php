<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\ClassSession;
use Illuminate\Support\Carbon;

class TeacherAvailabilityUrgencyService
{
    /**
     * Hitung tingkat urgensi untuk sebuah kelas yang menunggu jawaban guru:
     * - Urgent (Merah): sesi kelas terdekat hari ini, besok, atau dalam <= 3 hari.
     * - Menengah (Oranye): sesi kelas terdekat dalam minggu ini (> 3 hari dan <= 7 hari).
     * - Tidak Urgent (Netral): lebih dari 7 hari atau tanpa tanggal dekat.
     * - Terlewat (Abu-abu / Merah tua): sesi terdekat sudah lewat hari ini.
     */
    public static function evaluateClass(ClassModel $class): array
    {
        $today = today();

        // Cari tanggal sesi kelas terdekat dari class_sessions atau start_date
        $nearestSession = ClassSession::where('class_id', $class->id)
            ->whereNotNull('session_date')
            ->orderBy('session_date')
            ->first();

        $nearestDate = null;
        if ($nearestSession?->session_date) {
            $nearestDate = Carbon::parse($nearestSession->session_date)->startOfDay();
        } elseif ($class->start_date) {
            $nearestDate = Carbon::parse($class->start_date)->startOfDay();
        }

        if (!$nearestDate) {
            return [
                'level'       => 'neutral',
                'label'       => 'Tidak Urgent',
                'badge_class' => 'bg-secondary',
                'days_diff'   => 999,
                'target_date' => null,
            ];
        }

        $diffDays = $today->diffInDays($nearestDate, false);

        if ($diffDays < 0) {
            return [
                'level'       => 'past',
                'label'       => 'Terlewat (' . abs($diffDays) . ' hari lalu)',
                'badge_class' => 'bg-dark text-white',
                'days_diff'   => $diffDays,
                'target_date' => $nearestDate,
            ];
        }

        if ($diffDays <= 3) {
            $label = $diffDays === 0 ? 'Urgent (Hari Ini!)' : ($diffDays === 1 ? 'Urgent (Besok!)' : "Urgent ({$diffDays} hari lagi)");

            return [
                'level'       => 'urgent',
                'label'       => $label,
                'badge_class' => 'bg-danger text-white',
                'days_diff'   => $diffDays,
                'target_date' => $nearestDate,
            ];
        }

        if ($diffDays <= 7) {
            return [
                'level'       => 'medium',
                'label'       => "Menengah ({$diffDays} hari lagi)",
                'badge_class' => 'bg-warning text-dark',
                'days_diff'   => $diffDays,
                'target_date' => $nearestDate,
            ];
        }

        return [
            'level'       => 'neutral',
            'label'       => "Tidak Urgent ({$diffDays} hari lagi)",
            'badge_class' => 'bg-info text-dark',
            'days_diff'   => $diffDays,
            'target_date' => $nearestDate,
        ];
    }
}
