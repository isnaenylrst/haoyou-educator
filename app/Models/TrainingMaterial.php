<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingMaterial extends Model
{
    use HasFactory;

    protected $table = 'training_materials';

    protected $fillable = [
        'title',
        'category',
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
     * Cek apakah materi memiliki video (baik link URL maupun file lokal)
     */
    public function getHasVideoAttribute(): bool
    {
        return !empty($this->video_url) || !empty($this->video_path);
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

        // Pola YouTube standar: watch?v=ID atau youtu.be/ID atau youtube.com/embed/ID
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        return null;
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

