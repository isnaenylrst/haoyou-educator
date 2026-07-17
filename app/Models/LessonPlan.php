<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_schedule_id',
        'teacher_id',
        'learning_material_id',
        'title',
        'objective',
        'activity',
        'assessment',
        'status',
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
     * Relasi ke Learning Material
     */
    public function learningMaterial()
    {
        return $this->belongsTo(LearningMaterial::class);
    }

    /**
     * Relasi ke Review Log
     */
    public function reviewLogs()
    {
        return $this->hasMany(ReviewLog::class);
    }

    /**
     * Relasi ke PPT
     */
    public function ppts()
    {
        return $this->hasMany(Ppt::class);
    }

    /**
     * Relasi ke Teaching Log
     */
    public function teachingLogs()
    {
        return $this->hasMany(TeachingLog::class);
    }
}