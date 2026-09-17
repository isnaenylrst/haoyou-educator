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
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'notify_whatsapp' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'LP_PPT' => 'Konsultasi LP & PPT (Kurikulum)',
            'Direktur' => 'Konsultasi Direktur',
            'Trial_Teaching' => 'Trial Teaching',
            default => $this->type,
        };
    }
}