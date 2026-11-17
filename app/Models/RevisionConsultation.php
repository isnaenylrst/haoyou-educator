<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevisionConsultation extends Model
{
    use HasFactory;

    protected $table = 'revision_consultations';

    protected $fillable = [
        'teacher_id',
        'class_id',
        'document_type',
        'obstacle',
        'attachment_path',
        'attachment_name',
        'status',
        'curriculum_id',
        'curriculum_reply',
        'accepted_at',
        'completed_at',
    ];

    protected $casts = [
        'accepted_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function curriculum()
    {
        return $this->belongsTo(User::class, 'curriculum_id');
    }

    public function messages()
    {
        return $this->hasMany(RevisionConsultationMessage::class, 'revision_consultation_id')->orderBy('created_at', 'asc');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'Selesai', 'Diterima'  => 'bg-success text-white',
            'Perlu Revisi'         => 'bg-danger text-white',
            'Menunggu'             => 'bg-warning text-dark',
            default                => 'bg-secondary text-white',
        };
    }
}

