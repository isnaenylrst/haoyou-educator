<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AchievementLevel extends Model
{
    protected $table = 'achievement_levels';

    protected $fillable = [
        'level',
        'description',
        'sort_order',
    ];

    /** ['Excellent' => 'deskripsi...', ...] urut sesuai sort_order */
    public static function descriptionMap(): array
    {
        return static::orderBy('sort_order')->pluck('description', 'level')->all();
    }
}