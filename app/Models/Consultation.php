<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    protected $table = 'consultations';

    protected $fillable = [
        'teacher_id',
        'type',
        'scheduled_at',
        'status',
        'notify_whatsapp',
        'notes',
        'created_by',
        'teacher_material_id',
    ];

    protected $casts = [
        'scheduled_at'    => 'datetime',
        'notify_whatsapp' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teacherMaterial()
    {
        return $this->belongsTo(TeacherMaterial::class, 'teacher_material_id');
    }

    /** Konsultasi yang masih berjalan (belum selesai / dibatalkan). */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['Belum Terjadwal', 'Terjadwal']);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'LP_PPT'         => 'Konsultasi LP & PPT (Kurikulum)',
            'Direktur'       => 'Konsultasi Direktur',
            'Trial_Teaching' => 'Trial Teaching',
            default          => (string) $this->type,
        };
    }
}