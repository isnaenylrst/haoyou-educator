<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationResult extends Model
{
    use HasFactory;

    protected $table = 'consultation_results';

    protected $fillable = [
        'teacher_id',
        'curriculum_user_id',
        'observation_id',
        'consultation_date',
        'topic',
        'improvement_points',
        'follow_up',
        'file_path',
    ];

    protected $casts = [
        'consultation_date'  => 'date',
        'improvement_points' => 'array',
    ];

    /**
     * Relasi ke Guru
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /**
     * Relasi ke User Kurikulum yang membuat hasil konsultasi
     */
    public function curriculumUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'curriculum_user_id');
    }

    /**
     * Relasi opsional ke Observasi Guru (Poin 9)
     */
    public function observation(): BelongsTo
    {
        return $this->belongsTo(TeacherObservation::class, 'observation_id');
    }

    /**
     * Ambil nama file lampiran
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

