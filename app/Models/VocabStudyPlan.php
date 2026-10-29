<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabStudyPlan extends Model
{
    protected $guarded = [];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];
}
