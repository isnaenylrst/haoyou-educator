<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassTeacherOffer extends Model
{
    protected $fillable = [
        'class_id', 
        'teacher_id', 
        'status', 
        'response_note', 
        'responded_at'
    ];

    protected $casts = [
        'responded_at' => 'datetime'
    ];

    public function kelas()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
 
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }    
}