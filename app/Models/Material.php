<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $table = 'materials';

    protected $fillable = [
        'program_package_id',
        'uploaded_by',
        'meeting_number',
        'title',
        'syllabus',
        'material_file_path',
    ];

    public function programPackage()
    {
        return $this->belongsTo(ProgramPackage::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function vocabularies()
    {
        return $this->hasMany(MaterialVocab::class, 'material_id');
    }

    public function teacherMaterials()
    {
        return $this->hasMany(TeacherMaterial::class, 'material_id');
    }

    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'material_id');
    }
}