<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningVideo extends Model
{
    protected $table = 'learning_videos';

    protected $fillable = [
        'program_level_id',
        'title',
        'description',
        'source_type',
        'youtube_url',
        'youtube_id',
        'file_path',
        'mime_type',
        'file_size',
        'is_published',
        'uploaded_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function level()
    {
        return $this->belongsTo(ProgramLevel::class, 'program_level_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Hanya video yang sudah dipublikasikan Kurikulum. */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Video yang boleh dilihat siswa: yang untuk semua level (NULL)
     * atau yang levelnya sama dengan kelas aktif siswa.
     */
    public function scopeVisibleToLevels($query, array $levelIds)
    {
        return $query->published()->where(function ($q) use ($levelIds) {
            $q->whereNull('program_level_id');
            if (!empty($levelIds)) {
                $q->orWhereIn('program_level_id', $levelIds);
            }
        });
    }

    // ------------------------------------------------------------------ YOUTUBE

    public function getIsYoutubeAttribute(): bool
    {
        return $this->source_type === 'youtube' && !empty($this->youtube_id);
    }

    public function getEmbedUrlAttribute(): ?string
    {
        return $this->is_youtube
            ? 'https://www.youtube-nocookie.com/embed/' . $this->youtube_id
            : null;
    }

    public function getWatchUrlAttribute(): ?string
    {
        return $this->is_youtube ? self::watchUrlFor($this->youtube_id) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->is_youtube
            ? 'https://img.youtube.com/vi/' . $this->youtube_id . '/hqdefault.jpg'
            : null;
    }

    public static function watchUrlFor(string $id): string
    {
        return 'https://www.youtube.com/watch?v=' . $id;
    }

    /**
     * Mengambil ID video (11 karakter) dari berbagai bentuk link YouTube
     * (watch?v=, youtu.be, shorts, embed, live). Null jika tidak valid.
     */
    public static function extractYoutubeId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        $host  = strtolower(preg_replace('/^(www\.|m\.)/', '', $parts['host'] ?? ''));
        $path  = $parts['path'] ?? '';
        $id    = null;

        if ($host === 'youtu.be') {
            $id = trim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = $query['v'] ?? null;
            } elseif (preg_match('~^/(embed|shorts|live|v)/([^/?]+)~', $path, $m)) {
                $id = $m[2];
            }
        }

        return ($id && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) ? $id : null;
    }

    public function getSizeLabelAttribute(): string
    {
        if ($this->source_type === 'youtube') {
            return 'Link YouTube';
        }

        $bytes = (int) $this->file_size;

        if ($bytes <= 0) {
            return '-';
        }

        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024) . ' KB';
    }
}