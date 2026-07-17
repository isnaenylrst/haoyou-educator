<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

        'lesson_plan_id',
        'curriculum_id',
        'review_date',
        'status',
        'comment',

    ];

    protected $casts = [
        'review_date' => 'datetime',
    ];

    /**
     * Relasi ke Lesson Plan
     */
    public function lessonPlan()
    {
        return $this->belongsTo(LessonPlan::class);
    }

    /**
     * Relasi ke Curriculum
     */
    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }
}