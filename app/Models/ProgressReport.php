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

    public const FOLLOW_UPS = [
        'Belum',
        'Menunggu Jawaban',
        'Lanjut',
        'Tidak Lanjut',
    ];

    protected $fillable = [
        'student_id',
        'teacher_id',
        'enrollment_id',
        'report_type',
        'report_period',
        'file_path',
        'status',
        'uploaded_by',

        // Nilai kompetensi
        'listening_score',
        'reading_score',
        'writing_score',

        // Publikasi dan follow-up
        'published_at',
        'follow_up_status',
        'followed_up_at',
        'follow_up_note',

        // Konten dan review Kurikulum
        'content',
        'attendance_present',
        'attendance_total',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'returned_at',
        'submitted_at',
        'extended_until',
    ];

    protected $casts = [
        'published_at'   => 'datetime',
        'followed_up_at' => 'datetime',

        'content'        => 'array',
        'reviewed_at'    => 'datetime',
        'returned_at'    => 'datetime',
        'submitted_at'   => 'datetime',
        'extended_until' => 'date',
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

    /** Sudah di-ACC Kurikulum (baru boleh tampil ke siswa). */
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'Submitted'
            && $this->reviewed_at !== null;
    }

    /** Menunggu review Kurikulum. */
    public function getIsPendingReviewAttribute(): bool
    {
        return $this->status === 'Submitted'
            && $this->reviewed_at === null;
    }

    /** Dikembalikan Kurikulum dan belum dikirim ulang. */
    public function getIsReturnedAttribute(): bool
    {
        return $this->status === 'Draft'
            && $this->returned_at !== null;
    }

    /** Hanya report yang sudah di-ACC. */
    public function scopeApproved($query)
    {
        return $query
            ->where('status', 'Submitted')
            ->whereNotNull('reviewed_at');
    }

    /** Rata-rata nilai latihan dari semua kompetensi di report ini. */
    public function getAverageScoreAttribute(): ?float
    {
        $scores = collect($this->content ?? [])
            ->pluck('score')
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (float) $v);

        return $scores->isEmpty()
            ? null
            : round($scores->avg(), 1);
    }
}
