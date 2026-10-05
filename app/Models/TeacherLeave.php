<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherLeave extends Model
{
    use HasFactory;

    protected $table = 'teacher_leaves';

    protected $fillable = [
        'teacher_id',
        'class_schedule_id',
        'leave_date',
        'leave_type',
        'reason',
        'supporting_document',
        'replacement_teacher_id',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'leave_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function replacementTeacher()
    {
        return $this->belongsTo(Teacher::class, 'replacement_teacher_id');
    }

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Baru: satu leave bisa dipakai di banyak sesi jurnal (jaga-jaga kalau
    // ada kasus tidak umum), tapi normalnya 1:1 dengan teaching_journals.leave_date
    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'teacher_leave_id');
    }
}