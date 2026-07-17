<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_program',
        'tipe_kelas',
        'fokus',
        'durasi',
        'jumlah_pertemuan',
        'min_siswa',
        'max_siswa',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function prices()
    {
        return $this->hasMany(ProgramPrice::class);
    }

    public function classes()
    {
        return $this->hasMany(CourseClass::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}