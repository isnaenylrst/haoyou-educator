<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    use HasFactory;

    protected $table = 'alumni';

    protected $fillable = [
        'student_id',
        'updated_by',
        'tanggal_keluar',
        'alasan',
    ];

    protected $casts = [
        'tanggal_keluar' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}