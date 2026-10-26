<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressReport extends Model
{
    use HasFactory;

    protected $table = 'progress_reports';

    public $timestamps = false;
    public const SUBMITTED = 'Submitted';
    public const FOLLOW_UPS = ['Belum', 'Menunggu Jawaban', 'Lanjut', 'Tidak Lanjut'];


    protected $fillable = [
        'student_id',
        'teacher_id',
        'enrollment_id',
        'report_type',
        'report_period',
        'file_path',
        'status',
        'uploaded_by',
         'listening_score', 
         'reading_score', 
         'writing_score',
        'published_at',
        'follow_up_status', 
        'followed_up_at', 
        'follow_up_note',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'followed_up_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(ClassEnrollment::class, 'enrollment_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}