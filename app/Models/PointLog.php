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

    public function student()  { return $this->belongsTo(Student::class); }
    public function inputBy()  { return $this->belongsTo(User::class, 'input_by'); }

    public function getInputterLabelAttribute(): string
    {
        $name = $this->inputBy?->name ?? '-';
        return $this->input_role === 'admin' ? "Admin ({$name})" : "Guru {$name}";
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