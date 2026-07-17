<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Syllabus extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_id',
        'curriculum_id',
        'title',
        'level',
        'description',
        'status',
    ];

    /**
     * Relasi Program
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Relasi Curriculum
     */
    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }
}