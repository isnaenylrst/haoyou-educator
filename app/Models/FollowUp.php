<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_student_id',
        'template_id',
        'admin_id',
        'tanggal_followup',
        'media',
        'hasil_followup',
        'next_followup',
        'notes',
        'status',
    ];

    protected $casts = [
        'tanggal_followup' => 'datetime',
        'next_followup' => 'datetime',
    ];

    public function candidateStudent()
    {
        return $this->belongsTo(CandidateStudent::class);
    }

    public function template()
    {
        return $this->belongsTo(FollowupTemplate::class, 'template_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}