<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassLearningMaterial extends Model
{
    use HasFactory;

    protected $table = 'class_learning_materials';

    protected $fillable = [
        'class_id',
        'material_name',
        'sort_order',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}

