<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrammarProgress extends Model
{
    protected $table = 'grammar_progress';
    protected $fillable = ['user_id', 'kind', 'item_no', 'choice'];
}