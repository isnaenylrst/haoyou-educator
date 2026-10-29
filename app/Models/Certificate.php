<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $table = 'certificates';

    protected $fillable = [
        // Sertifikat ujian / progress report
        'user_id',
        'program_level_id',
        'progress_report_id',
        'verification_code',
        'student_name',
        'level_name',
        'level_number',
        'scores',
        'total_score',
        'max_total',
        'passing_score',
        'result',
        'issued_at',

        // Sertifikat kelas
        'enrollment_id',
        'student_id',
        'teacher_id',
        'course_name',
        'start_date',
        'end_date',
        'score',
        'status',
        'review_note',
        'reviewed_by',
        'reviewed_at',

        // File sertifikat
        'certificate_number',
        'file_path',
    ];

    protected $casts = [
        'scores'      => 'array',
        'issued_at'   => 'datetime',
        'start_date'  => 'date',
        'end_date'    => 'date',
        'score'       => 'float',
        'total_score' => 'float',
        'max_total'   => 'float',
        'passing_score' => 'float',
        'reviewed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI SERTIFIKAT UJIAN
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programLevel(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class);
    }

    public function progressReport(): BelongsTo
    {
        return $this->belongsTo(ProgressReport::class);
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI SERTIFIKAT KELAS
    |--------------------------------------------------------------------------
    */

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(
            ClassEnrollment::class,
            'enrollment_id'
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS SERTIFIKAT KELAS
    |--------------------------------------------------------------------------
    */

    /**
     * Sertifikat sudah disetujui oleh Kurikulum.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    /**
     * Sertifikat menunggu keputusan Kurikulum.
     */
    public function scopePendingReview($query)
    {
        return $query->where('status', 'Pending');
    }

    /*
    |--------------------------------------------------------------------------
    | HASIL SERTIFIKAT UJIAN
    |--------------------------------------------------------------------------
    */

    public function isPassed(): bool
    {
        return $this->result === 'passed';
    }

    public function getIsPassedAttribute(): bool
    {
        return $this->isPassed();
    }

    public function getResultLabelAttribute(): string
    {
        return $this->isPassed()
            ? 'LULUS'
            : 'TIDAK LULUS';
    }
}
