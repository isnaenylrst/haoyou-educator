<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    use HasFactory;

    protected $table = 'curriculum';

    protected $table = 'curriculums';

    protected $fillable = [
        'user_id',
        'level_id',
        'name',
        'phone',
        'address',
        'specialist',
        'join_date',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}