<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ppt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lesson_plan_id',
        'title',
        'file_path',
        'status',
    ];

    /**
     * Relasi ke Lesson Plan
     */
    public function lessonPlan()
    {
        return $this->belongsTo(LessonPlan::class);
    }
}