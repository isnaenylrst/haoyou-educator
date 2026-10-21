<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'user_id', 'program_level_id', 'progress_report_id',
        'certificate_number', 'verification_code',
        'student_name', 'level_name', 'level_number',
        'scores', 'total_score', 'max_total', 'passing_score',
        'result', 'issued_at', 'file_path',
    ];

    protected $casts = [
        'scores'    => 'array',
        'issued_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programLevel(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class);
    }

 public function isPassed(): bool
{
    return $this->result === 'passed';
}

public function getIsPassedAttribute(): bool
{
    return $this->isPassed();
}

public function getResultLabelAttribute(): string
{
    return $this->isPassed() ? 'LULUS' : 'TIDAK LULUS';
}
}
