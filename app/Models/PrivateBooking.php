<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PrivateBooking extends Model
{
    protected $table = 'private_bookings';

    protected $fillable = [
        'student_id',
        'enrollment_id',
        'teacher_id',
        'teacher_available_slot_id',
        'session_date',
        'start_time',
        'end_time',
        'delivery_mode',
        'status',
        'rescheduled_from_date',
        'rescheduled_at',
        'reschedule_count',
    ];

    protected $casts = [
        'session_date'          => 'date',
        'rescheduled_from_date' => 'date',
        'rescheduled_at'        => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(ClassEnrollment::class, 'enrollment_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function slot()
    {
        return $this->belongsTo(TeacherAvailableSlot::class, 'teacher_available_slot_id');
    }

    /** Waktu mulai sesi (Carbon, tanggal + jam). */
    public function startsAt(): Carbon
    {
        return Carbon::parse($this->session_date->format('Y-m-d') . ' ' . $this->start_time);
    }

    /** Aturan: reschedule minimal H-1 (24 jam) sebelum kelas dimulai. */
    public function canReschedule(): bool
    {
        return $this->status === 'Scheduled'
            && now()->addHours(24)->lte($this->startsAt());
    }
}
