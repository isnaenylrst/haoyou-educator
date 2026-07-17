<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgressReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

        'student_id',
        'class_id',
        'teacher_id',
        'template_progress_report_id',
        'report_date',

        'communication',
        'confidence',
        'listening',
        'reading',
        'writing',
        'behavior',

        'homework',
        'teacher_notes',
        'pdf_file',
        'status',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    /**
     * Student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Class
     */
    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class,'class_id');
    }

    /**
     * Teacher
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Template
     */
    public function template()
    {
        return $this->belongsTo(TemplateProgressReport::class,'template_progress_report_id');
    }
}