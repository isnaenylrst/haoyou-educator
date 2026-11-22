<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\ClassSession;
use App\Models\Consultation;
use App\Models\RevisionConsultation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MeetingConsultationRuleService
{
    /** Ukuran blok pertemuan. */
    public const BLOCK_SIZE = 4;

    /** Batas konsultasi materi baru: H-14 hari sebelum pertemuan pertama blok. */
    public const CONSULTATION_LEAD_DAYS = 14;

    /** Batas konsultasi revisi: H+3 hari setelah konsultasi dilakukan. */
    public const REVISION_LEAD_DAYS = 3;

    /**
     * Hitung catatan pertemuan blok untuk suatu kelas.
     * Dinamis mengikuti class_sessions aktual (termasuk hasil reschedule).
     */
    public function getBlocksForClass(ClassModel $class): Collection
    {
        // Ambil sesi kelas yang ada (diurutkan berdasarkan session_number / meeting_number / session_date)
        $sessions = ClassSession::where('class_id', $class->id)
            ->whereNotNull('session_date')
            ->orderBy('session_date')
            ->get();

        // Tentukan jumlah total pertemuan yang akan ditampilkan
        $maxMeetingNumber = (int) ($sessions->max(function ($s) {
            return $s->session_number ?? $s->meeting_number ?? 0;
        }) ?? 0);
        $totalMeetings = max($maxMeetingNumber, 4); // Minimal 1 blok (1-4)
        $totalBlocks = (int) ceil($totalMeetings / self::BLOCK_SIZE);

        $blocks = collect();

        for ($blockIndex = 1; $blockIndex <= $totalBlocks; $blockIndex++) {
            $startMeeting = (($blockIndex - 1) * self::BLOCK_SIZE) + 1;
            $endMeeting   = $blockIndex * self::BLOCK_SIZE;
            $blockLabel   = "Pertemuan {$startMeeting} - {$endMeeting}";

            // Pertemuan pertama dalam blok ini (cocokkan session_number atau meeting_number)
            $firstSession = $sessions->first(function ($s) use ($startMeeting) {
                return (int) ($s->session_number ?? $s->meeting_number ?? 0) === $startMeeting;
            });

            if (!$firstSession) {
                // Sesi untuk pertemuan pertama blok belum dijadwalkan di class_sessions
                $blocks->push([
                    'block_index'            => $blockIndex,
                    'block_label'            => $blockLabel,
                    'first_session_date'     => null,
                    'consultation_deadline'  => null,
                    'consultation_status'    => 'Belum dijadwalkan',
                    'consultation_status_badge' => 'secondary',
                    'revision_deadline'      => null,
                    'revision_status'        => '-',
                    'revision_status_badge'  => 'secondary',
                    'is_complete'            => false,
                    'is_overdue'             => false,
                ]);
                continue;
            }

            $firstDate = Carbon::parse($firstSession->session_date)->startOfDay();
            $consultationDeadline = $firstDate->copy()->subDays(self::CONSULTATION_LEAD_DAYS);

            // Cek konsultasi yang ada untuk kelas & guru terkait blok ini
            $consultation = Consultation::where('class_id', $class->id)
                ->where(function ($q) use ($startMeeting, $endMeeting) {
                    $q->where('notes', 'like', "%{$startMeeting}-{$endMeeting}%")
                      ->orWhere('notes', 'like', "%Pertemuan {$startMeeting}%")
                      ->orWhereIn('type', ['Materi', 'Materi_Baru']);
                })
                ->latest('scheduled_at')
                ->first();

            // Status Konsultasi
            $consultationStatus = 'Belum dijadwalkan';
            $consultationBadge  = 'secondary';
            $isOverdue          = false;
            $isComplete         = false;

            if ($consultation) {
                if ($consultation->status === 'Selesai') {
                    $consultationStatus = 'Selesai';
                    $consultationBadge  = 'success';
                    $isComplete         = true;
                } elseif ($consultation->status === 'Terjadwal') {
                    $consultationStatus = 'Terjadwal';
                    $consultationBadge  = 'primary';
                } elseif ($consultation->status === 'Dibatalkan') {
                    $consultationStatus = 'Dibatalkan';
                    $consultationBadge  = 'dark';
                }
            }

            if (!$isComplete && $consultationStatus !== 'Terjadwal') {
                if (today()->gt($consultationDeadline)) {
                    $consultationStatus = 'Terlambat';
                    $consultationBadge  = 'danger';
                    $isOverdue          = true;
                } elseif (today()->diffInDays($consultationDeadline, false) <= 3) {
                    $consultationStatus = 'Mendekati Batas';
                    $consultationBadge  = 'warning';
                }
            }

            // Batas Konsultasi Revisi (H+3 setelah tanggal konsultasi)
            $revisionDeadline = null;
            $revisionStatus   = '-';
            $revisionBadge    = 'secondary';

            $consultationDate = $consultation?->scheduled_at ? Carbon::parse($consultation->scheduled_at)->startOfDay() : null;

            if ($consultationDate) {
                $revisionDeadline = $consultationDate->copy()->addDays(self::REVISION_LEAD_DAYS);

                // Cek konsultasi revisi terkait kelas / guru ini
                $revision = RevisionConsultation::where('class_id', $class->id)
                    ->whereDate('created_at', '>=', $consultationDate)
                    ->latest()
                    ->first();

                if ($revision) {
                    if ($revision->status === 'Diterima' || $revision->status === 'Selesai') {
                        $revisionStatus = 'Selesai';
                        $revisionBadge  = 'success';
                    } elseif ($revision->status === 'Perlu Revisi') {
                        $revisionStatus = 'Perlu Revisi';
                        $revisionBadge  = 'warning';
                    } else {
                        $revisionStatus = 'Menunggu';
                        $revisionBadge  = 'info';
                    }
                } else {
                    if (today()->gt($revisionDeadline)) {
                        $revisionStatus = 'Terlambat';
                        $revisionBadge  = 'danger';
                    } else {
                        $revisionStatus = 'Belum Ada';
                        $revisionBadge  = 'light text-dark border';
                    }
                }
            }

            $blocks->push([
                'block_index'               => $blockIndex,
                'block_label'               => $blockLabel,
                'first_session_date'        => $firstDate,
                'consultation_deadline'     => $consultationDeadline,
                'consultation_status'       => $consultationStatus,
                'consultation_status_badge' => $consultationBadge,
                'revision_deadline'         => $revisionDeadline,
                'revision_status'           => $revisionStatus,
                'revision_status_badge'     => $revisionBadge,
                'is_complete'               => $isComplete,
                'is_overdue'                => $isOverdue,
            ]);
        }

        return $blocks;
    }
}
