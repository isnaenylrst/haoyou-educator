<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_id',
        'uploaded_by',
        'meeting_number',
        'title',
        'syllabus',
        'cco',
        'lesson_plan',
        'activity',
        'box_code',
        'equipment_list',
        'material_file_path',
    ];

    public function level()
    {
        return $this->belongsTo(ProgramLevel::class, 'level_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function vocabularies(): HasMany
    {
        return $this->hasMany(MaterialVocab::class, 'material_id');
    }

    public function teacherMaterials(): HasMany
    {
        return $this->hasMany(TeacherMaterial::class, 'material_id');
    }

    public function teachingJournals(): HasMany
    {
        return $this->hasMany(TeachingJournal::class, 'material_id');
    }

    // ------------------------------------------------------------ INFO FILE

    public function getFileExtensionAttribute(): string
    {
        return strtolower(pathinfo((string) $this->material_file_path, PATHINFO_EXTENSION));
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->file_extension === 'pdf';
    }

    public function getFileExistsAttribute(): bool
    {
        return $this->material_file_path
            && Storage::disk('public')->exists($this->material_file_path);
    }

    public function getFileSizeLabelAttribute(): string
    {
        if (!$this->file_exists) {
            return '-';
        }

        $bytes = Storage::disk('public')->size($this->material_file_path);

        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024) . ' KB';
    }
}