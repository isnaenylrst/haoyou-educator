<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateStudent extends Model
{
    use HasFactory;

    protected $table = 'candidate_student';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'gender',
        'birth_date',
        'phone',
        'parent_name',
        'no_parents',
        'address',
        'school',
        'source',
        'alergi',
        'interested_program',
        'status_trial',
        'tanggal_trial',
        'status_lead',
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