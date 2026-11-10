<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $table = 'submissions';

    public const TYPES = [
        'Reimburse Bahan Ajar',
        'Pembelian Bahan Ajar',
        'Pembelian Sertifikat',
    ];

    public const STATUSES = [
        'Diajukan',
        'Review Kurikulum',
        'Verifikasi Admin',
        'Review Owner',
        'Disetujui',
        'Dibayar',
        'Selesai',
        'Ditolak',
    ];

    protected $fillable = [
        'teacher_id',
        'created_by',
        'type',
        'item_name',
        'items',
        'amount',
        'date',
        'description',
        'receipt_path',
        'photo_path',
        'status',
        'curriculum_id',
        'curriculum_note',
        'curriculum_approved_at',
        'curriculum_signature_path',
        'admin_id',
        'admin_verified_at',
        'admin_note',
        'owner_id',
        'owner_note',
        'owner_approved_at',
        'owner_signature_path',
    ];

    protected $casts = [
        'date'                   => 'date',
        'items'                  => 'array',
        'curriculum_approved_at' => 'datetime',
        'admin_verified_at'      => 'datetime',
        'owner_approved_at'      => 'datetime',
        'amount'                 => 'decimal:2',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function curriculum()
    {
        return $this->belongsTo(User::class, 'curriculum_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'Diajukan'                           => 'bg-warning text-dark',
            'Review Kurikulum'                   => 'bg-info text-dark',
            'Verifikasi Admin'                   => 'bg-secondary text-white',
            'Review Owner'                       => 'bg-primary text-white',
            'Disetujui', 'Dibayar', 'Selesai'    => 'bg-success text-white',
            'Ditolak'                            => 'bg-danger text-white',
            default                              => 'bg-secondary text-white',
        };
    }
}

