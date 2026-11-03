<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function read(string $key): ?string
    {
        return static::where('key', $key)->value('value');
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Ambil 11 karakter ID dari link YouTube (watch, youtu.be, embed, shorts) */
    public static function youtubeId(?string $url): ?string
    {
        if ($url && preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }
        return null;
    }
}