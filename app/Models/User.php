<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'level_id',
        'username',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    public function level()
    {
        return $this->belongsTo(
            Level::class,
            'level_id', // foreign key di tabel users
            'id_level'  // primary key di tabel levels
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FIX: sebelumnya salah arah (nunjuk ke model Wali & kolom siswa_id).
    | Sekarang benar: 1 User -> 1 baris di tabel teachers (lewat user_id).
    |--------------------------------------------------------------------------
    */
    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | FIX: sebelumnya cuma return boolean (bukan relasi Eloquent beneran),
    | jadi tidak bisa dipakai ->student->nama dsb.
    |--------------------------------------------------------------------------
    */
    public function student()
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function curriculum()
    {
        return $this->hasOne(Curriculum::class);
    }

    public function uploadedMaterials()
    {
        return $this->hasMany(Material::class, 'uploaded_by');
    }

    public function uploadedProgressReports()
    {
        return $this->hasMany(ProgressReport::class, 'uploaded_by');
    }

    public function uploadedTemplates()
    {
        return $this->hasMany(DocumentTemplate::class, 'uploaded_by');
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function approvedTeacherMaterials()
    {
        return $this->hasMany(TeacherMaterial::class, 'approved_by');
    }

    public function approvedTeacherLeaves()
    {
        return $this->hasMany(TeacherLeave::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | BARU: satu sumber kebenaran untuk "user ini seharusnya diarahkan
    | ke route mana setelah login". Dipakai di AuthController DAN di
    | override guest-middleware (AppServiceProvider), supaya logic-nya
    | tidak ditulis dua kali di dua tempat berbeda.
    |--------------------------------------------------------------------------
    */
    public function homeRouteName(): ?string
    {
        return match ($this->level->nama_level ?? null) {
            'Curriculum' => 'kurikulum.dashboard',
            'Teacher'    => 'teacher.dashboard',
            'Student'    => 'dashboard',
            default      => null, // Owner/Admin/tidak dikenali -> belum ada modul
        };
    }
}