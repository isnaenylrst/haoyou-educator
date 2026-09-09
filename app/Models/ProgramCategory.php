<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'category_name',
        'min_age',
        'max_age',
        'sort_order',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function levels()
    {
        return $this->hasMany(ProgramLevel::class, 'category_id')->orderBy('sort_order');
    }
 
    public function packages()
    {
        return $this->hasMany(ProgramPackage::class, 'category_id');
    }
}