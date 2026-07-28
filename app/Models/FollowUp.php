<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $table = 'follow_ups';

    public $timestamps = false;

    protected $fillable = [
        'follow_up_template_id',
        'candidate_student_id',
        'followup_date',
        'followup_method',
        'note',
        'next_followup',
        'status',
    ];

    public function template()
    {
        return $this->belongsTo(FollowUpTemplate::class, 'follow_up_template_id');
    }

    public function candidateStudent()
    {
        return $this->belongsTo(CandidateStudent::class);
    }
}