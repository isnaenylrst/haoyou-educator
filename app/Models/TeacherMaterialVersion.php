<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherMaterialVersion extends Model
{
    use HasFactory;

    protected $table = 'teacher_material_versions';

    protected $fillable = [
        'teacher_material_id',
        'version_number',
        'lp_file_path',
        'ppt_file_path',
        'notes',
        'status',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'reviewed_at'    => 'datetime',
    ];

    public function teacherMaterial()
    {
        return $this->belongsTo(TeacherMaterial::class, 'teacher_material_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'Disetujui', 'Approved'     => 'bg-success text-white',
            'Perlu Revisi', 'Rejected'  => 'bg-danger text-white',
            default                     => 'bg-warning text-dark',
        };
    }
}

