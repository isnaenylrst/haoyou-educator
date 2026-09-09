<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'class_id',
        'class_schedule_id',
        'session_date',
        'material_id',
        'is_substitute',
        'substitute_teacher_id',
        'class_status',
        'learning_activities',
        'problems',
        'solutions',
        'results',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'is_substitute' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}