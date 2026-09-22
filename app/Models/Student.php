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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function candidateStudent()
    {
        return $this->belongsTo(CandidateStudent::class);
    }

    public function enrollments()
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    public function activeEnrollment()
    {
        return $this->hasOne(ClassEnrollment::class)
            ->whereIn('status', ['Active', 'Waiting Class'])
            ->latestOfMany('enrollment_date');
    }

    public function progressReports()
    {
        return $this->hasMany(ProgressReport::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(
            Payment::class,
            ClassEnrollment::class,
            'student_id',    // FK di class_enrollments ke students
            'enrollment_id', // FK di payments ke class_enrollments
            'id',
            'id'
        );
    }

    public function attendances()
    {
        return $this->hasManyThrough(
            Attendance::class,
            TeachingJournal::class,
        );
    }

    public function getAgeAttribute()
    {
        $birthDate = $this->candidateStudent?->birth_date;
        return $birthDate ? \Carbon\Carbon::parse($birthDate)->age : null;
    }
}