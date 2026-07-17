<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vocabulary extends Model
{
    use HasFactory;

    protected $fillable = [
        'learning_material_id',
        'hanzi',
        'pinyin',
        'meaning',
        'example',
    ];

    /**
     * Relasi ke Learning Material
     */
    public function learningMaterial()
    {
        return $this->belongsTo(LearningMaterial::class);
    }
}