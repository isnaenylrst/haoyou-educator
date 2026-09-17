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
        'private_package_id',
        'trial_status',
        'trial_date',
        'trial_notes',
        'lead_status',
        'registration_fee_paid_at',
        'registration_fee_proof_path',
        'registration_fee_status',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'trial_date' => 'date',
        'registration_fee_paid_at' => 'datetime',
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

    // Baru: calon siswa bisa tertarik ke program privat, bukan cuma reguler
    public function privatePackage()
    {
        return $this->belongsTo(PrivatePackage::class, 'private_package_id');
    }

    // Helper buat job ExpireRegistrationFees: cari calon siswa yang fee
    // pendaftarannya sudah lewat 30 hari, masih Active, dan belum jadi siswa.
    public function scopeEligibleForFeeExpiry($query, int $days = 30)
    {
        return $query->where('registration_fee_status', 'Active')
            ->whereNotNull('registration_fee_paid_at')
            ->where('registration_fee_paid_at', '<=', now()->subDays($days))
            ->whereDoesntHave('student');
    }
}