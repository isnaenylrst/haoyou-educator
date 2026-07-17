<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_schedule_id',
        'student_id',
        'teacher_id',
        'status',
        'notes',
    ];

    /**
     * Relasi ke Jadwal Kelas
     */
    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    /**
     * Relasi ke Student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relasi ke Teacher
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}