<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointLog extends Model
{
    protected $fillable = [
        'student_id', 'session_date', 'type', 'points', 'points_before',
        'points_after', 'input_by', 'input_role', 'teaching_journal_id',
    ];

    protected $casts = ['session_date' => 'date'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function inputBy()
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    /** Nama yang tampil di riwayat: "Admin (nama)" atau "Guru nama". */
    public function getInputterLabelAttribute(): string
    {
        $u = $this->inputBy;

        if ($this->input_role === 'guru') {
            $name = $u?->teacher?->name ?? $u?->name ?? $u?->nama ?? $u?->username;

            return $name ? "Guru {$name}" : 'Guru';
        }

        $name = $u?->name ?? $u?->nama ?? $u?->username ?? $u?->email;

        return $name ? "Admin ({$name})" : 'Admin';
    }

    public function getSummaryAttribute(): string
    {
        return sprintf(
            'Poin %s sudah diinput oleh %s pada %s',
            ucfirst($this->type),
            $this->inputter_label,
            $this->created_at->format('d/m/Y H:i')
        );
    }
}