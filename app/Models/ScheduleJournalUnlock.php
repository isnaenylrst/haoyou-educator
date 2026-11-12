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
        'status',          // 'Pending', 'Unlocked', 'Rejected'
        'request_reason',
        'requested_at',
        'requested_by',
        'unlocked_until',
        'unlocked_by',
        'rejection_note',
    ];

    protected $casts = [
        'session_date'   => 'date',
        'requested_at'   => 'datetime',
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

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'Unlocked' && $this->unlocked_until && $this->unlocked_until->isFuture();
    }
}
