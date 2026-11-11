<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleJournalUnlock extends Model
{
    use HasFactory;

    protected $table = 'schedule_journal_unlocks';

    protected $fillable = [
        'class_schedule_id',
        'session_date',
        'unlocked_until',
        'unlocked_by',
    ];

    protected $casts = [
        'session_date'   => 'date',
        'unlocked_until' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function unlocker()
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->unlocked_until && $this->unlocked_until->isFuture();
    }
}

