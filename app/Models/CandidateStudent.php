<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Program;

class CandidateStudent extends Model
{
    use HasFactory;

    protected $table = 'candidate_students';

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
        'program_id',
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
        return $this->morphMany(FollowUp::class, 'followupable');
    }

    public function latestFollowUp()
    {
        return $this->morphOne(FollowUp::class, 'followupable')
                    ->latestOfMany(['followup_date', 'id']);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }
}