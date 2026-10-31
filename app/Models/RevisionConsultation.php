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

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'Selesai'   => 'bg-success text-white',
            'Diterima'  => 'bg-info text-dark',
            default     => 'bg-warning text-dark',
        };
    }
}

