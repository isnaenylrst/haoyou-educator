<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $table = 'materials';

    protected $fillable = [
        'class_id',
        'uploaded_by',
        'meeting_number',
        'title',
        'syllabus',
        'material_file_path',
    ];

    protected $appends = [
        'material_url'
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
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

    public function getMaterialUrlAttribute(): string
    {
        return asset('storage/' . $this->material_file_path);
    }
}