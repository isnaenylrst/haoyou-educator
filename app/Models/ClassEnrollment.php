<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassEnrollment extends Model
{
    use HasFactory;

    protected $table = 'class_enrollments';

    protected $fillable = [
        'student_id',
        'class_id',
        'private_package_id',
        'enrollment_date',
        'status',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
    ];

    protected static function booted()
    {
        static::saving(function (ClassEnrollment $enrollment) {
            $filled = collect([$enrollment->class_id, $enrollment->private_package_id])
                ->filter()
                ->count();

            if ($filled !== 1) {
                throw new \InvalidArgumentException(
                    'Enrollment harus punya tepat satu target: class_id ATAU private_package_id.'
                );
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function privatePackage()
    {
        return $this->belongsTo(PrivatePackage::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'enrollment_id');
    }

    public function progressReports()
    {
        return $this->hasMany(ProgressReport::class, 'enrollment_id');
    }
}