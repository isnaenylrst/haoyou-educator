<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingJournal extends Model
{
    use HasFactory;

    protected $table = 'teaching_journals';

    protected $fillable = [
        'teacher_id',
        'class_id',
        'class_schedule_id',
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
        'is_substitute' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'teaching_journal_id');
    }
}