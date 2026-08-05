<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    public $timestamps = false;

    protected $fillable = [
        'program_package_id',
        'teacher_id',
        'class_name',
        'delivery_mode',
        'start_date',
        'end_date',
        'capacity',
        'status',
    ];

    public function programPackage()
    {
        return $this->belongsTo(ProgramPackage::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class, 'class_id');
    }

    public function enrollments()
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id');
    }

    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'class_id');
    }
    public function materials()
    {
    return $this->hasMany(Material::class, 'class_id');
    }
}