<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningVideo extends Model
{
    protected $table = 'learning_videos';

    protected $fillable = [
        'program_level_id',
        'category_id',
        'title',
        'description',
        'source_type',
        'kind',
        'youtube_url',
        'youtube_id',
        'file_path',
        'thumbnail_path',
        'mime_type',
        'file_size',
        'is_published',
        'is_active',
        'uploaded_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function level()
    {
        return $this->belongsTo(ProgramLevel::class, 'program_level_id');
    }

    public function category()
    {
        return $this->belongsTo(ProgramCategory::class, 'category_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Hanya video aktif/dipublikasikan. */
    public function scopeActive($query)
    {
        return $query->where('is_published', true)->where('is_active', true);
    }

    /**
     * Video yang boleh dilihat sesuai kategori / level kelas.
     */
    public function scopeVisibleToCategoriesAndLevels($query, array $categoryIds = [], array $levelIds = [])
    {
        return $query->active()->where(function ($q) use ($categoryIds, $levelIds) {
            // Video umum (tanpa kategori dan tanpa level)
            $q->where(function ($sub) {
                $sub->whereNull('category_id')->whereNull('program_level_id');
            });

            if (!empty($categoryIds)) {
                $q->orWhereIn('category_id', $categoryIds);
            }

            if (!empty($levelIds)) {
                $q->orWhereIn('program_level_id', $levelIds);
            }
        });
    }

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
        if ($this->thumbnail_path) {
            return asset('storage/' . $this->thumbnail_path);
        }

        if ($this->is_youtube) {
            return 'https://img.youtube.com/vi/' . $this->youtube_id . '/hqdefault.jpg';
        }

        return asset('assets/img/video-placeholder.png');
    }

    public static function watchUrlFor(string $id): string
    {
        return 'https://www.youtube.com/watch?v=' . $id;
    }

    public static function extractYoutubeId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }

        $patterns = [
            '/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|v\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
