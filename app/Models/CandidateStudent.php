<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    'interested_program',
    'trial_status',
    'trial_date',
    'lead_status',
];

    protected $casts = [
        'birth_date' => 'date',
        'trial_date' => 'date',
    ];
}