<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAvailability extends Model
{
    protected $table = 'teacher_availabilities';

    public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    protected $fillable = [
        'teacher_id',
        'day',
        'start_time',
        'end_time',
        'note',
        'created_by',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Apakah slot ketersediaan ini menutup penuh jadwal kelas (hari & jam)? */
    public function covers(string $day, string $start, string $end): bool
    {
        return $this->day === $day
            && substr($this->start_time, 0, 5) <= substr($start, 0, 5)
            && substr($this->end_time, 0, 5) >= substr($end, 0, 5);
    }
}