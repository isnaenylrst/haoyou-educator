<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'curriculum_id',
        'title',
        'description',
        'version',
        'file_path',
        'status',
    ];

    /**
     * Relasi ke Kepala Kurikulum
     */
    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }
}