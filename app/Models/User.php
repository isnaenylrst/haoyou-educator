<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
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
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI LEVEL
    |--------------------------------------------------------------------------
    */

    public function level()
    {
        return $this->belongsTo(
            Level::class,
            'level_id',
            'id_level'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI SISWA
    |--------------------------------------------------------------------------
    */

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI GURU
    |--------------------------------------------------------------------------
    */

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'user_id', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI CURRICULUM
    |--------------------------------------------------------------------------
    */

    public function curriculum(): HasOne
    {
        return $this->hasOne(Curriculum::class, 'user_id', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | NAMA TAMPILAN
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        return $this->student?->name
            ?? $this->teacher?->name
            ?? $this->username
            ?? 'Siswa';
    }

    /*
    |--------------------------------------------------------------------------
    | HALAMAN UTAMA BERDASARKAN LEVEL
    |--------------------------------------------------------------------------
    */

    public function homeRouteName(): ?string
    {
        return match ($this->level?->nama_level) {
            'Owner'      => 'owner.dashboard',
            'Curriculum' => 'kurikulum.dashboard',
            'Teacher'    => 'teacher.dashboard',
            'Student'    => 'dashboard',
            default      => null,
        };
    }
}