<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleRequest extends Model
{
    protected $table = 'schedule_requests';

    protected $fillable = [
        'student_id',
        'request_type',
        'program_id',
        'preferred_teacher_id',
        'preferred_date',
        'preferred_days',
        'preferred_start_time',
        'delivery_mode',
        'note',
        'status',
        'admin_note',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'handled_at'     => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function preferredTeacher()
    {
        return $this->belongsTo(Teacher::class, 'preferred_teacher_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
