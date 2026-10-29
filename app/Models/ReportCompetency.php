<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCompetency extends Model
{
    protected $table = 'report_competencies';

    protected $fillable = [
        'program_level_id',
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function level()
    {
        return $this->belongsTo(ProgramLevel::class, 'program_level_id');
    }

    /** Kompetensi aktif untuk satu level (termasuk yang berlaku umum / level NULL). */
    public function scopeForLevel($query, ?int $levelId)
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($levelId) {
                $q->whereNull('program_level_id');
                if ($levelId) {
                    $q->orWhere('program_level_id', $levelId);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}