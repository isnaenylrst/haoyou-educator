<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherMaterial extends Model
{
    use HasFactory;

    protected $table = 'teacher_materials';

    protected $fillable = [
        'material_id',
        'teacher_id',
        'class_id',
        'ppt_title',
        'ppt_file_path',
        'status',
        'current_version',
        'review_note',
        'approved_by',
        'approved_at',
        'lp_file_path',
    ];

    protected $casts = [
        'approved_at'     => 'datetime',
        'current_version' => 'integer',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function versions()
    {
        return $this->hasMany(TeacherMaterialVersion::class, 'teacher_material_id')->orderBy('version_number');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Relasi yang hilang
    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }
}