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

    public function getSizeLabelAttribute(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes <= 0) {
            return '-';
        }

        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024) . ' KB';
    }
}