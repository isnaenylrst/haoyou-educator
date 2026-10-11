<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramVideo extends Model
{
    protected $fillable = [
        'type', 'audience', 'level_id', 'title',
        'video_path', 'duration_minutes', 'is_published',
    ];

    protected $casts = ['is_published' => 'boolean'];

    public function level()
    {
        return $this->belongsTo(ProgramLevel::class, 'level_id'); // SESUAIKAN nama model level
    }
}