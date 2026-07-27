<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
        return $this->belongsTo(Level::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
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
}