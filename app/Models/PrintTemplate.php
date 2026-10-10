<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
    protected $table = 'print_templates';

    protected $fillable = ['key', 'name', 'content', 'default_content'];

    public function getRouteKeyName(): string
    {
        return 'key';
    }
}