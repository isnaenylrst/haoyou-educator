<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfStudyCertificate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'issued_at'    => 'datetime',
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}