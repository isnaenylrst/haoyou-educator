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

    /** Batasi kata sesuai level siswa (null = tidak dibatasi). */
    public function scopeForLevel($q, $levelId)
    {
        return $levelId ? $q->where("program_level_id", $levelId) : $q;
    }
}