<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUpTemplate extends Model
{
    use HasFactory;

    protected $table = 'follow_up_template';

    public $timestamps = false;

    protected $fillable = [
        'template_name',
        'category',
        'description',
    ];

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }
}