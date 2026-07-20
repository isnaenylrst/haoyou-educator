<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curriculum extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'curriculums';

    protected $fillable = [
        'user_id',
        'nama',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // User Login
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // SOP
    public function sops()
    {
        return $this->hasMany(Sop::class);
    }

    // Learning Materials
    public function learningMaterials()
    {
        return $this->hasMany(LearningMaterial::class);
    }

    // Syllabus
    public function syllabi()
    {
        return $this->hasMany(Syllabus::class);
    }

    // Consultation Schedule
    public function consultationSchedules()
    {
        return $this->hasMany(ConsultationSchedule::class);
    }

    // Review Log
    public function reviewLogs()
    {
        return $this->hasMany(ReviewLog::class);
    }
}