<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_name',
        'description',
    ];

    public function categories()
    {
        return $this->hasMany(ProgramCategory::class);
    }

    public function levels()
    {
        return $this->hasMany(ProgramLevel::class);
    }

    public function packages()
    {
        return $this->hasMany(ProgramPackage::class);
    }
}