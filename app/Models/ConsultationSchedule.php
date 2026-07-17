<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultationSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'curriculum_id',
        'consultation_date',
        'topic',
        'result',
        'status',
    ];

    protected $casts = [
        'consultation_date' => 'datetime',
    ];

    /**
     * Relasi ke Teacher
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Relasi ke Curriculum
     */
    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }
}