<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabChapter extends Model
{
    protected $guarded = [];

    public function level() { return $this->belongsTo(ProgramLevel::class, "program_level_id"); }
    public function words() { return $this->hasMany(VocabWord::class)->orderBy("order_number"); }
}