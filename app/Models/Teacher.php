<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $table = 'teacher';

    protected $fillable = [
        'user_id',
        'level_id',
        'name',
        'phone',
        'address',
        'specialist',
        'join_date',
        'training_status',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class);
    }

    public function substituteTeachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'substitute_teacher_id');
    }

    public function teacherMaterials()
    {
        return $this->hasMany(TeacherMaterial::class);
    }

    public function teacherLeaves()
    {
        return $this->hasMany(TeacherLeave::class);
    }

    public function replacementLeaves()
    {
        return $this->hasMany(TeacherLeave::class, 'replacement_teacher_id');
    }

    public function progressReports()
    {
        return $this->hasMany(ProgressReport::class);
    }
}