<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'class_id',
        'class_schedule_id',
        'class_session_id',
        'session_date',
        'material_id',
        'is_substitute',
        'substitute_teacher_id',
        'teacher_leave_id',
        'class_status',
        'learning_activities',
        'problems',
        'solutions',
        'results',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'is_substitute' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    // Pertemuan terencana yang dicatat jurnal ini (null untuk jurnal lama)
    public function classSession()
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }

    // Menyatukan pencatatan guru pengganti dengan pengajuan cuti terkait
    public function teacherLeave()
    {
        return $this->belongsTo(TeacherLeave::class, 'teacher_leave_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}