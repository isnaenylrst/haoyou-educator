<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabExample extends Model
{
    protected $guarded = [];
    public function word() { return $this->belongsTo(VocabWord::class, 'vocab_word_id'); }
}
