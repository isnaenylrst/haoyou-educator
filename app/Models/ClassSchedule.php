<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'tanggal',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'ruangan',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class, 'class_id');
    }

    public function pointHistories()
    {
        return $this->hasMany(PointHistory::class);
    }
}