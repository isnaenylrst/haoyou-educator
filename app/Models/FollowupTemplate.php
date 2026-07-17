<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FollowupTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_template',
        'kategori',
        'media',
        'isi_template',
        'version',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function followUps()
    {
        return $this->hasMany(FollowUp::class, 'template_id');
    }
}