<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Riwayat follow up admin untuk lanjut program (satu baris per kontak). */
class EnrollmentFollowUp extends Model
{
    public const STATUSES = [
        'Sudah Dihubungi',
        'Tertarik Lanjut',
        'Pikir-pikir',
        'Akan Bayar',
        'Tidak Lanjut',
    ];

    protected $fillable = ['enrollment_id', 'status', 'followed_up_at', 'note', 'created_by'];

    protected $casts = ['followed_up_at' => 'date'];

    public function enrollment()
    {
        return $this->belongsTo(ClassEnrollment::class, 'enrollment_id');
    }
}