<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrammarQuestion extends Model
{
    protected $guarded = [];
    protected $casts   = ['options' => 'array'];

    public function scopeForLevel($q, $levelId)
    {
        return $levelId ? $q->where('program_level_id', $levelId) : $q;
    }

    public function point()
    {
        return $this->belongsTo(GrammarPoint::class, 'grammar_point_id');
    }
}