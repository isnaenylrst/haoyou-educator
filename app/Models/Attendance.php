<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'teaching_journal_id',
        'student_id',
        'attendance_date',
        'session_date',
        'status',
        'note',
    ];

    // attendance_date dipakai Monitoring Kurikulum, session_date dipakai jurnal.
    // Keduanya harus ikut tersimpan (lihat $fillable).
    protected $casts = [
        'attendance_date' => 'date',
        'session_date'    => 'date',
    ];

    public function teachingJournal()
    {
        return $this->belongsTo(TeachingJournal::class, 'teaching_journal_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    protected static function booted()
    {
        // session_date di sini adalah CACHE dari teaching_journals.session_date
        // (bukan sumber kebenaran). Auto-isi supaya guru/admin tidak perlu
        // input manual dan tidak ada celah tanggal berbeda dari jurnalnya.
        static::creating(function (Attendance $attendance) {
            if (!$attendance->session_date && $attendance->teaching_journal_id) {
                $attendance->session_date = optional(
                    TeachingJournal::find($attendance->teaching_journal_id)
                )->session_date;
            }
        });
    }
}