<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';

    public $timestamps = false;

    protected $fillable = [
        'candidate_student_id',
        'user_id',
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

    public function enrollments()
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    public function activeEnrollment()
    {
        return $this->hasOne(ClassEnrollment::class)
            ->where('status', 'Active')
            ->latestOfMany('enrollment_date');
    }    

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function progressReports()
    {
        return $this->hasMany(ProgressReport::class);
    }
}