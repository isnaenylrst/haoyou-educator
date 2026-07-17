<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

        'teacher_id',
        'class_schedule_id',
        'replacement_teacher_id',
        'type',
        'reason',
        'status',
        'approved_by',
        'approved_at',

    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Guru yang mengajukan izin
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Jadwal kelas
     */
    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    /**
     * Guru pengganti
     */
    public function replacementTeacher()
    {
        return $this->belongsTo(
            Teacher::class,
            'replacement_teacher_id'
        );
    }

    /**
     * Kepala Kurikulum yang menyetujui
     */
    public function approver()
    {
        return $this->belongsTo(
            Curriculum::class,
            'approved_by'
        );
    }

}