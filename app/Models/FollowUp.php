<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $table = 'follow_ups';

    protected $fillable = [
        'follow_up_template_id',
        'followupable_id',
        'followupable_type',
        'followup_date',
        'followup_method',
        'note',
        'next_followup',
        'status',
    ];

    public function template()
    {
        return $this->belongsTo(FollowUpTemplate::class, 'follow_up_template_id');
    }

    public function followupable()
    {
        return $this->morphTo();
    }
}