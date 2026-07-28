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
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Menentukan apakah program termasuk program HSK.
     */
    public function isHsk(): bool
    {
        // Cek berdasarkan kolom fokus
        if (!empty($this->fokus) && strtoupper($this->fokus) === 'HSK') {
            return true;
        }

        // Alternatif: cek dari nama program
        return str_contains(strtolower($this->nama_program), 'hsk');
    }

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