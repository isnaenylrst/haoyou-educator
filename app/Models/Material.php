<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_id',
        'uploaded_by',
        'meeting_number',
        'title',
        'syllabus',
        'material_file_path',
    ];

    public function level()
    {
        return $this->belongsTo(ProgramLevel::class, 'level_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}