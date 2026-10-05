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

    public function student()
    {
        return $this->hasOne(
            Student::class,
            'user_id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI GURU
    |--------------------------------------------------------------------------
    */

    public function teacher()
    {
        return $this->hasOne(
            Teacher::class,
            'user_id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI CURRICULUM
    |--------------------------------------------------------------------------
    */

    public function curriculum()
    {
        return $this->hasOne(
            Curriculum::class,
            'user_id',
            'id'
        );
    }
}