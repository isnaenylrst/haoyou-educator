<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PrivatePackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_name',
        'duration_minutes',
        'total_meetings',
        'min_students',
        'max_students',
        'price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function isUsedByEnrollments(): bool
    {
        return DB::table('class_enrollments')
            ->where('private_package_id', $this->id)
            ->exists();
    }
}