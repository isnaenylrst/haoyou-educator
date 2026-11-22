<?php

namespace App\Services;

use App\Models\CurriculumConsultationSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ConsultationSlotGridService
{
    /**
     * Buat data grid mingguan (Hari x Jam) dari koleksi slot konsultasi.
     * Mirip dengan weeklyGrid pada TeacherAvailabilityService.
     *
     * @param Carbon $startOfWeek Tanggal awal minggu (Senin)
     * @param Collection $slots CurriculumConsultationSlot
     * @param int|null $viewerUserId ID user yang sedang melihat
     * @param string $viewerRole 'Curriculum', 'Owner', atau 'Teacher'
     */
    public static function buildWeeklyGrid(Carbon $startOfWeek, $slots, ?int $viewerUserId = null, string $viewerRole = 'Curriculum'): array
    {
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $dt = $startOfWeek->copy()->addDays($i);
            $dayName = match ($dt->dayOfWeekIso) {
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                default => 'Minggu',
            };
            $days[] = [
                'date'      => $dt->toDateString(),
                'day_name'  => $dayName,
                'formatted' => $dt->translatedFormat('d M'),
                'is_today'  => $dt->isToday(),
            ];
        }

        // Rentang jam operasional: Senin - Jumat 09:00 - 21:00, Sabtu 09:00 - 18:00
        $startHour = 9;
        $endHour   = 21;

        foreach ($slots as $s) {
            $hStart = (int) substr($s->start_time, 0, 2);
            $hEnd   = (int) substr($s->end_time, 0, 2);
            if ((int) substr($s->end_time, 3, 2) > 0) {
                $hEnd++;
            }
            $startHour = min($startHour, $hStart);
            $endHour   = max($endHour, $hEnd);
        }

        $startHour = max(0, $startHour);
        $endHour   = min(24, max(21, $endHour));
        $hours     = range($startHour, $endHour - 1);

        $cells = [];
        foreach ($hours as $h) {
            foreach ($days as $d) {
                $cells[$h][$d['date']] = [];
            }
        }

        foreach ($slots as $slot) {
            $slotDateStr = $slot->date instanceof Carbon ? $slot->date->toDateString() : substr((string) $slot->date, 0, 10);
            $sMin = ((int) substr($slot->start_time, 0, 2) * 60) + (int) substr($slot->start_time, 3, 2);
            $eMin = ((int) substr($slot->end_time, 0, 2) * 60) + (int) substr($slot->end_time, 3, 2);

            $role = $slot->effective_role; // 'Curriculum' atau 'Owner'
            $isMine = ($viewerUserId && $slot->curriculum_user_id == $viewerUserId);
            $isPast = $slot->is_past;

            // Info pemesanan sebagian jam / sub-bookings
            $availMinutes = $slot->available_minutes;
            $totalMinutes = max(0, $eMin - $sMin);
            $isFullyBooked = ($slot->status === 'Booked' && $slot->booked_by_teacher_id) || ($totalMinutes > 0 && $availMinutes <= 0);
            $isPartiallyBooked = ($availMinutes > 0 && $availMinutes < $totalMinutes);

            // Status display
            if ($isPast && ($slot->status === 'Available' || $availMinutes > 0)) {
                $statusLabel = 'Terlewat';
                $badgeClass  = 'bg-secondary text-white';
            } elseif ($slot->status === 'Cancelled') {
                $statusLabel = 'Nonaktif';
                $badgeClass  = 'bg-light text-muted border';
            } elseif ($isFullyBooked) {
                $statusLabel = 'Penuh';
                $badgeClass  = 'bg-danger text-white';
            } elseif ($isPartiallyBooked) {
                $statusLabel = 'Sisa ' . ($availMinutes / 60) . 'j';
                $badgeClass  = 'bg-warning text-dark';
            } else {
                $statusLabel = 'Tersedia';
                $badgeClass  = 'bg-success text-white';
            }

            $isBookedByMe = false;
            if ($viewerRole === 'Teacher' && $viewerUserId) {
                $viewerTeacher = \App\Models\Teacher::where('user_id', $viewerUserId)->first();
                if ($viewerTeacher && $slot->booked_by_teacher_id == $viewerTeacher->id) {
                    $isBookedByMe = true;
                }
            }

            // Sub-booking items untuk slot ini jika ada
            $subsData = [];
            foreach ($slot->subBookings as $sub) {
                if ($sub->status === 'Booked') {
                    $subTeacherId = $sub->booked_by_teacher_id;
                    $isSubBookedByMe = ($viewerRole === 'Teacher' && $viewerUserId && $viewerTeacher && $subTeacherId == $viewerTeacher->id);
                    $subsData[] = [
                        'id'                     => $sub->id,
                        'start_time'             => $sub->effective_start_time,
                        'end_time'               => $sub->effective_end_time,
                        'teacher_name'           => $sub->teacher?->name,
                        'topic'                  => $sub->topic,
                        'is_booked_by_me'        => $isSubBookedByMe,
                        'teacher_booking_status' => $sub->teacher_booking_status,
                    ];
                }
            }

            foreach ($hours as $h) {
                $cellStart = $h * 60;
                $cellEnd   = ($h + 1) * 60;

                if ($sMin < $cellEnd && $eMin > $cellStart) {
                    if (isset($cells[$h][$slotDateStr])) {
                        $cells[$h][$slotDateStr][] = [
                            'id'                     => $slot->id,
                            'slot'                   => $slot,
                            'role'                   => $role,
                            'start_time'             => substr($slot->start_time, 0, 5),
                            'end_time'               => substr($slot->end_time, 0, 5),
                            'status'                 => $isFullyBooked ? 'Booked' : $slot->status,
                            'status_label'           => $statusLabel,
                            'badge_class'            => $badgeClass,
                            'is_mine'                => $isMine,
                            'is_booked_by_me'        => $isBookedByMe,
                            'is_past'                => $isPast,
                            'teacher_name'           => $slot->teacher?->name,
                            'topic'                  => $slot->topic ?: ($slot->booking_notes ? \Illuminate\Support\Str::limit($slot->booking_notes, 25) : null),
                            'notes'                  => $slot->notes,
                            'teacher_booking_status' => $slot->teacher_booking_status,
                            'available_minutes'      => $availMinutes,
                            'total_minutes'          => $totalMinutes,
                            'is_partially_booked'    => $isPartiallyBooked,
                            'is_fully_booked'        => $isFullyBooked,
                            'sub_bookings'           => $subsData,
                        ];
                    }
                }
            }
        }

        return [
            'start_of_week' => $startOfWeek->toDateString(),
            'prev_week'     => $startOfWeek->copy()->subWeek()->toDateString(),
            'next_week'     => $startOfWeek->copy()->addWeek()->toDateString(),
            'current_week'  => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'days'          => $days,
            'hours'         => $hours,
            'cells'         => $cells,
            'total_slots'   => $slots->count(),
        ];
    }
}
