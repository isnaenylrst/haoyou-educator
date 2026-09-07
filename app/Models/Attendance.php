<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'teaching_journal_id',
        'student_id',
        'status',
        'note',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function teachingJournal()
    {
        return $this->belongsTo(TeachingJournal::class, 'teaching_journal_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}