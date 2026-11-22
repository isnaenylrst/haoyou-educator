<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurriculumConsultationSlot extends Model
{
    use HasFactory;

    protected $table = 'curriculum_consultation_slots';

    protected $fillable = [
        'curriculum_user_id',
        'parent_slot_id',
        'owner_role',
        'date',
        'start_time',
        'end_time',
        'booked_start_time',
        'booked_end_time',
        'notes',
        'topic',
        'status',
        'booked_by_teacher_id',
        'class_id',
        'booking_notes',
        'booked_at',
        'teacher_booking_status',
        'reschedule_history',
    ];

    protected $casts = [
        'date'               => 'date',
        'booked_at'          => 'datetime',
        'reschedule_history' => 'array',
    ];

    /**
     * Default role jika kolom owner_role kosong (legacy) adalah 'Curriculum'
     */
    public function getEffectiveRoleAttribute(): string
    {
        return $this->owner_role ?: 'Curriculum';
    }

    public const BLOCK_MINUTES = 60;

    /**
     * Sisa durasi (menit) yang belum dipesan pada jendela waktu slot ini.
     */
    public function getAvailableMinutesAttribute(): int
    {
        $startMin = ((int) substr($this->start_time, 0, 2) * 60) + (int) substr($this->start_time, 3, 2);
        $endMin   = ((int) substr($this->end_time, 0, 2) * 60) + (int) substr($this->end_time, 3, 2);
        $totalWindow = max(0, $endMin - $startMin);

        // Jika slot ini sendiri Booked dan bukan parent (pemesanan penuh / legacy)
        if ($this->status === 'Booked' && !$this->parent_slot_id && $this->booked_by_teacher_id) {
            return 0;
        }

        // Hitung total menit yang sudah dipesan oleh subBookings
        $bookedMins = 0;
        $subs = $this->relationLoaded('subBookings') ? $this->subBookings : $this->subBookings()->get();
        foreach ($subs as $sub) {
            if ($sub->status === 'Booked') {
                $subStart = ((int) substr($sub->booked_start_time ?: $sub->start_time, 0, 2) * 60) + (int) substr($sub->booked_start_time ?: $sub->start_time, 3, 2);
                $subEnd   = ((int) substr($sub->booked_end_time ?: $sub->end_time, 0, 2) * 60) + (int) substr($sub->booked_end_time ?: $sub->end_time, 3, 2);
                $bookedMins += max(0, $subEnd - $subStart);
            }
        }

        return max(0, $totalWindow - $bookedMins);
    }

    /**
     * Ambil sub-slot / rentang waktu blok (default 60 menit) yang masih tersedia di dalam jendela waktu slot ini.
     * @return array<array{start: string, end: string}>
     */
    public function getAvailableSubSlots(): array
    {
        $startMin = ((int) substr($this->start_time, 0, 2) * 60) + (int) substr($this->start_time, 3, 2);
        $endMin   = ((int) substr($this->end_time, 0, 2) * 60) + (int) substr($this->end_time, 3, 2);
        $windowDuration = $endMin - $startMin;

        // Jika slot ini sudah dipesan secara penuh
        if ($this->status === 'Booked' && $this->booked_by_teacher_id) {
            return [];
        }

        // Ambil pemesanan aktif di slot ini (sub-bookings)
        $subs = $this->relationLoaded('subBookings') ? $this->subBookings : $this->subBookings()->get();
        $bookedRanges = [];
        foreach ($subs as $sub) {
            if ($sub->status === 'Booked') {
                $bStart = ((int) substr($sub->booked_start_time ?: $sub->start_time, 0, 2) * 60) + (int) substr($sub->booked_start_time ?: $sub->start_time, 3, 2);
                $bEnd   = ((int) substr($sub->booked_end_time ?: $sub->end_time, 0, 2) * 60) + (int) substr($sub->booked_end_time ?: $sub->end_time, 3, 2);
                $bookedRanges[] = ['start' => $bStart, 'end' => $bEnd];
            }
        }

        $blockMinutes = self::BLOCK_MINUTES;
        if ($windowDuration < $blockMinutes) {
            // Jika durasi jendela < 60 menit, dipesan penuh sekaligus bila belum ada pemesanan
            if (empty($bookedRanges)) {
                return [[
                    'start' => substr($this->start_time, 0, 5),
                    'end'   => substr($this->end_time, 0, 5),
                ]];
            }
            return [];
        }

        $availableBlocks = [];
        for ($cur = $startMin; $cur + $blockMinutes <= $endMin; $cur += $blockMinutes) {
            $curEnd = $cur + $blockMinutes;
            $overlap = false;
            foreach ($bookedRanges as $b) {
                if ($cur < $b['end'] && $curEnd > $b['start']) {
                    $overlap = true;
                    break;
                }
            }
            if (!$overlap) {
                $availableBlocks[] = [
                    'start' => sprintf('%02d:%02d', intdiv($cur, 60), $cur % 60),
                    'end'   => sprintf('%02d:%02d', intdiv($curEnd, 60), $curEnd % 60),
                ];
            }
        }

        return $availableBlocks;
    }

    public function parentSlot()
    {
        return $this->belongsTo(self::class, 'parent_slot_id');
    }

    public function subBookings()
    {
        return $this->hasMany(self::class, 'parent_slot_id');
    }

    /**
     * Jam efektif mulai untuk pemesanan slot ini (booked_start_time jika diisi, jika tidak start_time).
     */
    public function getEffectiveStartTimeAttribute(): string
    {
        return substr($this->booked_start_time ?: $this->start_time, 0, 5);
    }

    /**
     * Jam efektif selesai untuk pemesanan slot ini (booked_end_time jika diisi, jika tidak end_time).
     */
    public function getEffectiveEndTimeAttribute(): string
    {
        return substr($this->booked_end_time ?: $this->end_time, 0, 5);
    }

    public function curriculumUser()
    {
        return $this->belongsTo(User::class, 'curriculum_user_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'booked_by_teacher_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'Available');
    }

    public function scopeForCurriculum($query)
    {
        return $query->where(function ($q) {
            $q->where('owner_role', 'Curriculum')
              ->orWhereNull('owner_role');
        });
    }

    public function scopeForOwner($query)
    {
        return $query->where('owner_role', 'Owner');
    }

    public function scopeUpcoming($query)
    {
        return $query->where(function ($q) {
            $q->whereDate('date', '>', today())
              ->orWhere(function ($q2) {
                  $q2->whereDate('date', today())
                     ->whereTime('start_time', '>=', now()->format('H:i:s'));
              });
        });
    }

    public function getIsPastAttribute(): bool
    {
        if (!$this->date) {
            return false;
        }

        $end = \Illuminate\Support\Carbon::parse($this->date->toDateString() . ' ' . $this->end_time);

        return $end->isPast();
    }
}
