<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabAnswerLog extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts   = ['answered_at' => 'datetime'];

    public function word()
    {
        return $this->belongsTo(VocabWord::class, 'vocab_word_id');
    }
}