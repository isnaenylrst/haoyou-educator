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
        'owner_role',
        'date',
        'start_time',
        'end_time',
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
