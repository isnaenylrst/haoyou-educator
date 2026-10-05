<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'category_id',
        'level_name',
        'sort_order',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function category()
    {
        return $this->belongsTo(ProgramCategory::class, 'category_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class, 'level_id');
    }

    public function classes()
    {
        return $this->hasMany(ClassModel::class, 'level_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'current_level_id');
    }
}