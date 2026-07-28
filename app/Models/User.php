<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'nama_lengkap',
        'email',
        'no_whatsapp',
        'password',
        'role',
        'status_akun',
        'akses_diberikan_oleh',
        'akses_diberikan_pada',
        'foto_profil',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'akses_diberikan_pada' => 'datetime',
        ];
    }

    // ----- Relasi -----
    public function wali()
    {
        return $this->hasOne(Wali::class, 'siswa_id');
    }

    // ----- Helper status (dipakai LoginController) -----
    public function isAktif(): bool
    {
        return $this->status_akun === 'aktif';
    }

    public function isAlumni(): bool
    {
        return $this->status_akun === 'alumni';
    }

    public function isPendingVerifikasi(): bool
    {
        return $this->status_akun === 'pending_verifikasi';
    }

    // Relasi poin/jadwal/sertifikat/progress report ditambahkan lagi
    // nanti saat modul Dashboard mulai dikerjakan.
}