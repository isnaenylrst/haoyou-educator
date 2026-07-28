<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $table = 'class_schedules';

    public $timestamps = false;

    protected $fillable = [
        'class_id',
        'day',
        'start_time',
        'end_time',
        'room',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'class_schedule_id');
    }

    public function teacherLeaves()
    {
        return $this->hasMany(TeacherLeave::class, 'class_schedule_id');
    }
}