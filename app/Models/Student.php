<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_student_id',
        'user_id',
        'current_level_id',
        'name',
        'points',
        'join_date',
        'status',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    public function candidateStudent()
    {
        return $this->belongsTo(CandidateStudent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentLevel()
    {
        return $this->belongsTo(ProgramLevel::class, 'current_level_id');
    }

    public function enrollments()
    {
        return $this->hasMany(ClassEnrollment::class);
    }
}