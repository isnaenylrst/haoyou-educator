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
        'program_package',
        'trial_status',
        'trial_date',
        'lead_status',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'trial_date' => 'date',
    ];

    protected $appends = [
        'age',
    ];
    
    public function getAgeAttribute()
    {
        if (!$this->birth_date) {
            return null;
        }

        return $this->birth_date->age;
    }    

    public function availableSchedules()
    {
        return $this->hasMany(CandidateStudentAvailableSchedule::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function latestFollowUp()
    {
        return $this->hasOne(FollowUp::class)
                    ->latestOfMany('followup_date');
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function programPackage()
    {
        return $this->belongsTo(ProgramPackage::class, 'program_package', 'package_name');
    }
}