<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $table = 'class_schedules';

    protected $fillable = [
        'class_id',
        'day',
        'start_time',
        'end_time',
        'room',
        'zoom_link',
        'zoom_passcode',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'class_schedule_id');
    }

    public function teacherLeaves()
    {
        return $this->hasMany(TeacherLeave::class, 'class_schedule_id');
    }

    // Pertemuan bertanggal yang dibangkitkan dari pola mingguan ini.
    public function sessions()
    {
        return $this->hasMany(ClassSession::class, 'class_schedule_id');
    }

    /* ----------------------------------------------------------
     * Scope masa berlaku
     * ---------------------------------------------------------- */

    /** Pola yang belum ditutup (effective_until kosong). */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('effective_until');
    }

    /** Pola yang berlaku pada tanggal tertentu. */
    public function scopeActiveOn(Builder $query, $date): Builder
    {
        return $query
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $date);
            });
    }

    /* ----------------------------------------------------------
     * Helper
     * ---------------------------------------------------------- */

    public function isCurrent(): bool
    {
        return $this->effective_until === null;
    }

    /**
     * Tutup pola ini pada tanggal yang ditentukan.
     * Jadwal lama tetap disimpan agar jurnal lama tetap terhubung.
     */
    public function closeOn($lastDate): void
    {
        $this->update(['effective_until' => $lastDate]);
    }
}
