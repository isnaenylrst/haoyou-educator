<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    public $timestamps = false;

    protected $fillable = [
        'teaching_jurnal_id',
        'student_id',
        'status',
        'note',
        'attendance_date',
        'created_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function teachingJournal()
    {
        return $this->belongsTo(TeachingJournal::class, 'teaching_jurnal_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}