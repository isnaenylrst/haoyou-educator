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
        'program_package_id',   // <-- ditambahkan
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
            // Tidak boleh reguler DAN private sekaligus
            if ($enrollment->class_id && $enrollment->private_package_id) {
                throw new \InvalidArgumentException(
                    'Enrollment tidak boleh punya class_id dan private_package_id sekaligus.'
                );
            }

            // Harus tetap ada satu "identitas paket": program_package_id (reguler, kelas boleh menyusul)
            // ATAU private_package_id (private, class_id memang selalu kosong)
            $hasProgramTrack = $enrollment->program_package_id !== null;
            $hasPrivateTrack = $enrollment->private_package_id !== null;

            if (!$hasProgramTrack && !$hasPrivateTrack) {
                throw new \InvalidArgumentException(
                    'Enrollment harus punya program_package_id (paket reguler) atau private_package_id (paket private).'
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

    public function programPackage()
    {
        return $this->belongsTo(ProgramPackage::class);
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