<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function curriculum()
    {
        return $this->hasOne(Curriculum::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class, 'admin_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'admin_id');
    }

    public function documents()
    {
        return $this->hasMany(StudentDocument::class, 'uploaded_by');
    }

    public function alumniUpdates()
    {
        return $this->hasMany(Alumni::class, 'updated_by');
    }

    public function pointHistories()
    {
        return $this->hasMany(PointHistory::class, 'given_by');
    }
}