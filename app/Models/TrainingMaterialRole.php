<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingMaterialRole extends Model
{
    use HasFactory;

    protected $table = 'training_material_roles';

    protected $fillable = [
        'training_material_id',
        'role',
    ];

    public function trainingMaterial(): BelongsTo
    {
        return $this->belongsTo(TrainingMaterial::class, 'training_material_id');
    }
}

