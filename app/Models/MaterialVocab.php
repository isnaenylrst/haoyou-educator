<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialVocab extends Model
{
    use HasFactory;

    protected $table = 'materials_vocab';

    public $timestamps = false;

    protected $fillable = [
        'material_id',
        'hanzi',
        'pinyin',
        'meaning',
        'example_sentence',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}