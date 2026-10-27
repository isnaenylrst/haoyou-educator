<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateRule extends Model
{
    protected $fillable = ['level_number', 'components', 'passing_score', 'max_total'];

    protected $casts = [
        'components' => 'array',
    ];
}
