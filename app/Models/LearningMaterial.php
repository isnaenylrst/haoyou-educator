<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_id',
        'curriculum_id',
        'title',
        'level',
        'meeting',
        'description',
        'file_path',
        'status',
    ];

    protected $casts = [
        'meeting' => 'integer',
    ];

    /**
     * Relasi ke Program
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Relasi ke Curriculum
     */
    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    /**
     * Satu materi dapat digunakan di banyak Lesson Plan
     */
    public function lessonPlans()
    {
        return $this->hasMany(LessonPlan::class);
    }

    /**
     * Satu materi memiliki banyak Vocabulary
     */
    public function vocabularies()
    {
        return $this->hasMany(Vocabulary::class);
    }
}