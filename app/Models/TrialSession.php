<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrialSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_student_id',
        'teacher_id',
        'tanggal_trial',
        'hasil_trial',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal_trial' => 'datetime',
    ];

    public function candidateStudent()
    {
        return $this->belongsTo(CandidateStudent::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}