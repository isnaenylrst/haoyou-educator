<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassTask extends Model
{
    protected $table = 'class_tasks';

    protected $fillable = [
        'class_id',
        'teacher_id',
        'title',
        'description',
        'due_date',
        'status',
        'done_at',
        'teacher_note',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'done_at'  => 'datetime',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'Open'
            && $this->due_date
            && $this->due_date->endOfDay()->isPast();
    }
}