<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProgramPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'nama_paket',
        'nominal',
        'payment_scheme',
        'max_termin',
        'effective_from',
        'effective_until',
        'status',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
        'status' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}