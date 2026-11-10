<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ProgressReportTemplate extends Model
{
    protected $fillable = ['name', 'program_category_id', 'program_level_id', 'updated_by'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProgramCategory::class, 'program_category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class, 'program_level_id');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(ProgressReportTemplateMeeting::class, 'template_id')->orderBy('meeting_number');
    }

    /**
     * Template untuk sebuah kelas: cocokkan level kelas dulu, lalu kategori program level tsb
     * (Feixiang / Maochong / HSK ...). Null bila Kurikulum belum membuatnya.
     */
    public static function forClass(int $classId): ?self
    {
        $levelId = DB::table('classes')->where('id', $classId)->value('level_id');
        if (!$levelId) {
            return null;
        }

        $byLevel = static::with('meetings')->where('program_level_id', $levelId)->first();
        if ($byLevel && $byLevel->meetings->isNotEmpty()) {
            return $byLevel;
        }

        $categoryId = DB::table('program_levels')->where('id', $levelId)->value('category_id');
        if (!$categoryId) {
            return null;
        }

        $byCategory = static::with('meetings')
            ->where('program_category_id', $categoryId)
            ->whereNull('program_level_id')
            ->first();

        return ($byCategory && $byCategory->meetings->isNotEmpty()) ? $byCategory : null;
    }

    /** Nama program/level kelas (untuk pesan ke guru). */
    public static function programLabelForClass(int $classId): string
    {
        $row = DB::table('classes as c')
            ->join('program_levels as pl', 'pl.id', '=', 'c.level_id')
            ->leftJoin('program_categories as pc', 'pc.id', '=', 'pl.category_id')
            ->where('c.id', $classId)
            ->first(['pc.category_name', 'pl.level_name']);

        return $row?->category_name ?: ($row?->level_name ?: 'program ini');
    }
}