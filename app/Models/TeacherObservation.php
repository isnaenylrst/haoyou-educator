<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherObservation extends Model
{
    use HasFactory;

    protected $table = 'teacher_observations';

    protected $fillable = [
        'teacher_id',
        'class_id',
        'curriculum_user_id',
        'observation_date',
        'sop_compliance',
        'observation_result',
        'feedback_solution',
    ];

    protected $casts = [
        'observation_date' => 'date',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function curriculumUser()
    {
        return $this->belongsTo(User::class, 'curriculum_user_id');
    }

    public function getSopBadgeAttribute(): string
    {
        return match ($this->sop_compliance) {
            'Sesuai SOP'       => 'bg-success text-white',
            'Perlu Perbaikan'  => 'bg-warning text-dark',
            'Tidak Sesuai SOP' => 'bg-danger text-white',
            default            => 'bg-secondary text-white',
        };
    }
}

