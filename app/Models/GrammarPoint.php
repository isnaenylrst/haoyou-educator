<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrammarPoint extends Model
{
    protected $guarded = [];
    protected $casts   = ['contoh' => 'array'];

    public function scopeForLevel($q, $levelId)
    {
        return $levelId ? $q->where('program_level_id', $levelId) : $q;
    }
}