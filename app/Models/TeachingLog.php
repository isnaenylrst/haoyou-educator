<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeachingLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_schedule_id',
        'teacher_id',
        'lesson_plan_id',
        'summary',
        'obstacle',
        'follow_up',
    ];

    /**
     * Relasi ke Teacher
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Relasi ke Class Schedule
     */
    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    /**
     * Relasi ke Lesson Plan
     */
    public function lessonPlan()
    {
        return $this->belongsTo(LessonPlan::class);
    }
}