<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'program_package_id',
        'level_id',
        'teacher_id',
        'class_name',
        'delivery_mode',
        'start_date',
        'end_date',
        'capacity',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function programPackage()
    {
        return $this->belongsTo(ProgramPackage::class);
    }

    public function level()
    {
        return $this->belongsTo(ProgramLevel::class, 'level_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function enrollments()
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id');
    }
}