<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidateStudent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nama',
        'jenis_kelamin',
        'tanggal_lahir',
        'usia',
        'no_hp',
        'email',
        'nama_ortu',
        'no_hp_ortu',
        'alamat',
        'sekolah',
        'sumber',
        'kebutuhan_belajar',
        'available_schedule',
        'alergi',
        'catatan',
        'status_lead',
        'status_trial',
        'tanggal_trial',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_trial' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function trialSessions()
    {
        return $this->hasMany(TrialSession::class);
    }

    public function agreement()
    {
        return $this->hasOne(StudentAgreement::class);
    }

    public function documents()
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }
}