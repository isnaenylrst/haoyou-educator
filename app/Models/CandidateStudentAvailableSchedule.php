<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateStudentAvailableSchedule extends Model
{
    use HasFactory;

    protected $table = 'candidate_student_available_schedules';

    protected $fillable = [
        'candidate_student_id',
        'day',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time'   => 'datetime:H:i',
    ];

    public function candidateStudent()
    {
        return $this->belongsTo(CandidateStudent::class);
    }
}