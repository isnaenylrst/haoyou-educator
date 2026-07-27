<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramPackage extends Model
{
    use HasFactory;

    protected $table = 'program_packages';

    public $timestamps = false;

    protected $fillable = [
        'program_id',
        'course_type',
        'package_name',
        'duration_minutes',
        'total_meetings',
        'min_students',
        'max_students',
        'price',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }
}