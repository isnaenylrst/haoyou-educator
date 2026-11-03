<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabWord extends Model
{
    protected $guarded = [];
    protected $casts = ["trap_only" => "boolean"];

    public function examples()    { return $this->hasMany(VocabExample::class)->orderBy("sort_order"); }
    public function trap()        { return $this->hasMany(VocabTrap::class); }
    public function testResults() { return $this->hasMany(VocabTestResult::class); }
    public function level()       { return $this->belongsTo(ProgramLevel::class, "program_level_id"); }
    public function chapter()     { return $this->belongsTo(VocabChapter::class, "vocab_chapter_id"); }

    /**
     * ID semua level yang "setara" (program_id + level_name sama).
     * Contoh: HSK 3 ada di id 51 dan 57 -> keduanya memakai kata yang sama.
     */
    public static function equivalentLevelIds($levelId): array
    {
        $lvl = ProgramLevel::find($levelId);
        if (! $lvl) return [(int) $levelId];

        return ProgramLevel::where('program_id', $lvl->program_id)
            ->where('level_name', $lvl->level_name)->pluck('id')->all();
    }

    /** Batasi kata sesuai level siswa (null = tidak dibatasi). */
    public function scopeForLevel($q, $levelId)
    {
        return $levelId ? $q->whereIn('program_level_id', self::equivalentLevelIds($levelId)) : $q;
    }
}