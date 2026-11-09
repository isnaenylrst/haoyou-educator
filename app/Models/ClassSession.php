<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Services\ClassScheduleService;

class ClassSession extends Model
{
    protected $table = 'class_sessions';

    protected $fillable = [
        'class_id',
        'class_enrollment_id',
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

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ClassEnrollment::class,'class_enrollment_id')
            ->withTrashed();
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class,'class_schedule_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class,'teacher_id');
    }

    public function journal(): HasOne
    {
        return $this->hasOne(TeachingJournal::class,'class_session_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class,'class_session_id');
    }

    public function homeVisitPayment(): HasOne
    {
        return $this->hasOne(Payment::class,'class_session_id')
            ->where('payment_type',Payment::TYPE_HOME_VISIT);
    }

    public function replacementPayment(): HasOne
    {
        return $this->hasOne(Payment::class,'class_session_id')
            ->where('payment_type',Payment::TYPE_REPLACEMENT
        );
    }

    /* ---------------------------------------------------------- */
    /*  Scope                                                      */
    /* ---------------------------------------------------------- */

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'Scheduled');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'Scheduled')
            ->whereDate('session_date', '>=', today());
    }

    public function scopeCountsTowardPackage(Builder $query): Builder
    {
        return $query
            ->where('status', '!=', 'Cancelled')
            ->where('kind', 'Regular');
    }

    public function scopeForTeacher(
        Builder $query,int $teacherId): Builder {
            return $query->where('teacher_id', $teacherId);
        }

    public function scopeOnDate(
        Builder $query,$date): Builder {
            return $query->whereDate('session_date', $date);
        }

    public function scopeOverlapping(
        Builder $query,string $start,string $end): Builder {
            return $query
                ->where('status', '!=', 'Cancelled')
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start);
        }

    /* ---------------------------------------------------------- */
    /*  Helper                                                     */
    /* ---------------------------------------------------------- */

    public function isLocked(): bool
    {
        if ($this->status === 'Completed') {
            return true;
        }

        return $this->relationLoaded('journal')
            ? $this->journal !== null
            : $this->journal()->exists();
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'Scheduled'
            && ! $this->isLocked()
            && $this->session_date->lte(today());
    }

    public function canBeRescheduled(): bool
    {
        return $this->status === 'Scheduled'
            && ! $this->isLocked()
            && $this->session_date->gte(today());
    }


    public function canBecomeHomeVisit(): bool
    {
        return $this->status === 'Scheduled'
            && ! $this->isLocked()
            && $this->session_date->gte(today())
            && ! $this->isHomeVisit();
    }


    public function deviatesFromPattern(): bool
    {
        $p = $this->schedule;

        if (! $p) {
            return false;
        }

        return ClassScheduleService::DAYS[
            $this->session_date->dayOfWeekIso - 1
        ] !== $p->day
            || substr($this->start_time, 0, 5)
                !== substr($p->start_time, 0, 5)
            || substr($this->end_time, 0, 5)
                !== substr($p->end_time, 0, 5)
            // Ruang hanya dibandingkan untuk pertemuan di studio.
            // Home Visit tidak punya ruang.
            || (
                $this->location_type === 'Studio'
                && (string) $this->room !== (string) $p->room
            );
    }

    public function isHomeVisit(): bool
    {
        return $this->location_type === 'Home Visit';
    }

    public function isReplacement(): bool
    {
        return $this->kind === 'Replacement';
    }

    public function countsTowardPackage(): bool
    {
        return $this->status !== 'Cancelled'
            && $this->kind === 'Regular';
    }
}