<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAvailableSlot extends Model
{
    protected $table = 'teacher_available_slots';

    protected $fillable = [
        'teacher_id',
        'day',
        'start_time',
        'end_time',
        'delivery_mode',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
