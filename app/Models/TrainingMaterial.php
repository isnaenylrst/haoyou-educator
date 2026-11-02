<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingMaterial extends Model
{
    use HasFactory;

    protected $table = 'training_materials';

    public const LEVELS = ['Umum', 'Dasar', 'Menengah', 'Lanjutan'];
    public const ROLES  = ['All', 'Admin', 'Curriculum', 'Teacher'];
    public const TYPES  = ['document', 'video'];

    protected $fillable = [
        'title',
        'category',
        'target_role',
        'level',
        'type',
        'thumbnail_path',
        'version',
        'description',
        'file_path',
        'video_url',
        'video_path',
        'uploaded_by',
    ];

    /**
     * Relasi ke user yang mengunggah (Owner)
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope filter per peran (tiap peran hanya melihat miliknya atau yang ditujukan ke All)
     */
    public function scopeForRole(Builder $query, string $role): Builder
    {
        $normalized = match ($role) {
            'Admin'                 => ['Admin', 'All'],
            'Curriculum', 'Kurikulum' => ['Curriculum', 'Kurikulum', 'All'],
            'Teacher', 'Guru'       => ['Teacher', 'Guru', 'All'],
            default                 => ['All'],
        };

        return $query->whereIn('target_role', $normalized);
    }

    /**
     * Scope video
     */
    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('type', 'video')
            ->orWhereNotNull('video_url')
            ->orWhereNotNull('video_path');
    }

    /**
     * Scope dokumen
     */
    public function scopeDocuments(Builder $query): Builder
    {
        return $query->where('type', 'document')
            ->orWhereNotNull('file_path');
    }

    /**
     * Cek apakah materi memiliki video
     */
    public function getHasVideoAttribute(): bool
    {
        return !empty($this->video_url) || !empty($this->video_path) || $this->type === 'video';
    }

    /**
     * Cek apakah link adalah Google Drive
     */
    public function getIsDriveLinkAttribute(): bool
    {
        if (empty($this->video_url)) {
            return false;
        }

        return str_contains($this->video_url, 'drive.google.com');
    }

    /**
     * Konversi link YouTube ke format embed URL jika berupa YouTube
     */
    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        if (empty($this->video_url)) {
            return null;
        }

        $url = trim($this->video_url);

        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // Jika Google Drive preview embed
        if (str_contains($url, 'drive.google.com/file/d/')) {
            preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $dMatches);
            if (!empty($dMatches[1])) {
                return 'https://drive.google.com/file/d/' . $dMatches[1] . '/preview';
            }
        }

        return null;
    }

    /**
     * Badge CSS untuk level materi
     */
    public function getLevelBadgeAttribute(): string
    {
        return match ($this->level) {
            'Dasar'    => 'bg-info text-dark',
            'Menengah' => 'bg-warning text-dark',
            'Lanjutan' => 'bg-danger text-white',
            default    => 'bg-secondary text-white',
        };
    }

    /**
     * Badge CSS untuk target role
     */
    public function getTargetRoleBadgeAttribute(): string
    {
        return match ($this->target_role) {
            'Admin'      => 'bg-primary text-white',
            'Curriculum' => 'bg-purple text-white',
            'Teacher'    => 'bg-success text-white',
            default      => 'bg-dark text-white',
        };
    }

    /**
     * Ambil nama file asli / basename
     */
    public function getFileNameAttribute(): ?string
    {
        return $this->file_path ? basename($this->file_path) : null;
    }

    /**
     * Ambil ekstensi file
     */
    public function getFileExtensionAttribute(): ?string
    {
        return $this->file_path ? strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)) : null;
    }
}
