<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAgreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_student_id',
        'agreement_number',
        'agreement_version',
        'agreement_pdf',
        'signed_by',
        'signed_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'signed_date' => 'date',
    ];

    public function candidateStudent()
    {
        return $this->belongsTo(CandidateStudent::class);
    }
}