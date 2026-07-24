<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $table = 'programs';

    public $timestamps = false;

    protected $fillable = [
        'program_name',
        'description',
    ];

    public function programPackages()
    {
        return $this->hasMany(ProgramPackage::class);
    }
}