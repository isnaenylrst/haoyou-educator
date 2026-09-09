<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProgramPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'category_id',
        'level_id',
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
    public function category()
    {
        return $this->belongsTo(ProgramCategory::class, 'category_id');
    }

    public function level()
    {
        return $this->belongsTo(ProgramLevel::class, 'level_id');
    }
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function isUsedByClasses(): bool
    {
        return DB::table('classes')
            ->where('program_package_id', $this->id)
            ->exists();
    }
}