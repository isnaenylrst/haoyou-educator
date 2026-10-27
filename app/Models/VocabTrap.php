<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabTrap extends Model
{
    protected $guarded = [];
    protected $casts = ['explanations' => 'array'];
    public function word() { return $this->belongsTo(VocabWord::class, 'vocab_word_id'); }
}
