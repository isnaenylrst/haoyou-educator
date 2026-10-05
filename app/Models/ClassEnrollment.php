<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'class_enrollments';

    protected $fillable = [
        'student_id',
        'class_id',
        'program_package_id',
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
            // Yang tidak boleh bersamaan itu DUA IDENTITAS PAKET (reguler vs
            // private), BUKAN class_id vs private_package_id — sebuah kelas
            // privat justru SEHARUSNYA punya class_id + private_package_id
            // terisi bersamaan begitu siswa ditempatkan ke kelasnya (classes
            // table sudah bisa menampung private_package_id sendiri).
            $hasProgramTrack = $enrollment->program_package_id !== null;
            $hasPrivateTrack = $enrollment->private_package_id !== null;

            if ($hasProgramTrack && $hasPrivateTrack) {
                throw new \InvalidArgumentException(
                    'Enrollment tidak boleh mengisi program_package_id dan private_package_id sekaligus.'
                );
            }

            if (!$hasProgramTrack && !$hasPrivateTrack) {
                throw new \InvalidArgumentException(
                    'Enrollment harus mengisi salah satu: program_package_id (paket reguler) atau private_package_id (paket private).'
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