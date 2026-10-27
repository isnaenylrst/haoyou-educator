<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabTestResult extends Model
{
    protected $guarded = [];
    protected $casts = ['is_ragu' => 'boolean'];
    public function word() { return $this->belongsTo(VocabWord::class, 'vocab_word_id'); }
}
