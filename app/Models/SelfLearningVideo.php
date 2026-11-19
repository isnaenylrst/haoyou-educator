<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfLearningVideo extends Model
{
    protected $table = 'self_learning_videos';

    protected $fillable = [
        'module', 'title', 'description', 'source_type',
        'youtube_url', 'youtube_id', 'file_path', 'mime_type',
        'file_size', 'is_active', 'uploaded_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /** Video aktif untuk satu modul (vocab / grammar), atau null. */
    public static function activeFor(string $module): ?self
    {
        return static::where('module', $module)
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();
    }

    public function getIsYoutubeAttribute(): bool
    {
        return $this->source_type === 'youtube' && !empty($this->youtube_id);
    }

    public function getIsUploadAttribute(): bool
    {
        return $this->source_type === 'upload' && !empty($this->file_path);
    }

    public function getEmbedUrlAttribute(): ?string
    {
        return $this->is_youtube
            ? 'https://www.youtube-nocookie.com/embed/' . $this->youtube_id
            : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->is_youtube
            ? 'https://img.youtube.com/vi/' . $this->youtube_id . '/hqdefault.jpg'
            : null;
    }

    public function getModuleLabelAttribute(): string
    {
        return $this->module === 'grammar' ? 'Belajar Grammar' : 'Belajar Vocab';
    }
}