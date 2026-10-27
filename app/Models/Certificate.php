<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $table = 'certificates';

    protected $fillable = [
        'enrollment_id',
        'student_id',
        'teacher_id',
        'certificate_number',
        'course_name',
        'start_date',
        'end_date',
        'score',
        'file_path',
        'status',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'score'       => 'float',
        'reviewed_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(ClassEnrollment::class, 'enrollment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Sudah di-ACC Kurikulum (baru boleh tampil di siswa). */
    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    /** Menunggu keputusan Kurikulum. */
    public function scopePendingReview($query)
    {
        return $query->where('status', 'Pending');
    }
}