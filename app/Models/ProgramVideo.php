<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramVideo extends Model
{
    protected $fillable = [
        'type', 'level_id', 'chapter_number', 'title',
        'youtube_id', 'duration_minutes', 'is_published',
    ];

    protected $casts = ['is_published' => 'boolean'];

    public function level()
    {
        return $this->belongsTo(ProgramLevel::class, 'level_id');
    }

    /** Ambil ID video dari link YouTube apa pun (watch, youtu.be, shorts, embed, live). */
    public static function extractYoutubeId(?string $url): ?string
    {
        $url = trim((string) $url);

        if (preg_match('~^[A-Za-z0-9_-]{11}$~', $url)) {
            return $url;
        }

        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    public function getEmbedUrlAttribute(): string
    {
        return 'https://www.youtube-nocookie.com/embed/' . $this->youtube_id
            . '?rel=0&modestbranding=1&playsinline=1';
    }
}