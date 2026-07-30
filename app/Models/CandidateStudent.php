<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateStudent extends Model
{
    use HasFactory;

    protected $table = 'candidate_students';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'gender',
        'birth_date',
        'phone',
        'parent_name',
        'parent_phone',
        'address',
        'school',
        'source',
        'allergy',
        'interested_program',
        'trial_status',
        'trial_date',
        'lead_status',
    ];

    public function availableSchedules()
    {
        return $this->hasMany(CandidateStudentAvailableSchedule::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }
}