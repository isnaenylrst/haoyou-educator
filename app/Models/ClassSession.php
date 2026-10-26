<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClassSession extends Model
{
    protected $table = 'class_sessions';

    protected $fillable = [
        'class_id',
        'class_schedule_id',
        'kind',
        'session_number',
        'session_date',
        'start_time',
        'end_time',
        'room',
        'teacher_id',
        'location_type',
        'status',
        'original_date',
        'change_reason',
    ];

    protected $casts = [
        'session_date'  => 'date',
        'original_date' => 'date',
    ];

    /* ---------------------------------------------------------- */
    /*  Relasi                                                     */
    /* ---------------------------------------------------------- */

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /** Jurnal mengajar untuk pertemuan ini (ada setelah guru mengisi jurnal). */
    public function journal(): HasOne
    {
        return $this->hasOne(TeachingJournal::class, 'class_session_id');
    }

    /* ---------------------------------------------------------- */
    /*  Scope                                                      */
    /* ---------------------------------------------------------- */

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'Scheduled');
    }

    /** Pertemuan yang belum terjadi (hari ini ke depan). */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'Scheduled')
            ->whereDate('session_date', '>=', today());
    }

    public function scopeForTeacher(Builder $query, int $teacherId): Builder
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeOnDate(Builder $query, $date): Builder
    {
        return $query->whereDate('session_date', $date);
    }

    /**
     * Pertemuan yang jamnya beririsan dengan rentang $start-$end.
     * Dipakai untuk cek bentrok guru. Pertemuan batal tidak dihitung.
     */
    public function scopeOverlapping(Builder $query, string $start, string $end): Builder
    {
        return $query->where('status', '!=', 'Cancelled')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }

    /* ---------------------------------------------------------- */
    /*  Helper                                                     */
    /* ---------------------------------------------------------- */

    /** Pertemuan yang sudah selesai atau sudah punya jurnal tidak boleh diubah/dihapus. */
    public function isLocked(): bool
    {
        return $this->status === 'Completed' || $this->journal()->exists();
    }

    public function isHomeVisit(): bool
    {
        return $this->location_type === 'Home Visit';
    }

    public function isReplacement(): bool
    {
        return $this->kind === 'Replacement';
    }
}