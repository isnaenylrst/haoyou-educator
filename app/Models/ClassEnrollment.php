<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'last_class_date',
        'registration_fee',
        'activity_fee',
        'discount',
        'notes',
        'requested_material',
        'home_visit_address',
        'payment_scheme',
        'termin_count',
        'form_manual',
        'is_additional_program',
        'status',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'last_class_date' => 'date',
        'termin_count' => 'integer',
        'form_manual' => 'array',
        'is_additional_program' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function (ClassEnrollment $enrollment) {
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

    public function packagePayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'enrollment_id')
            ->where('payment_type', Payment::TYPE_PACKAGE);
    }

    public function extraPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'enrollment_id')
            ->whereIn('payment_type', Payment::EXTRA_TYPES);
    }

    public function replacementSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'class_enrollment_id')
            ->where('kind', 'Replacement');
    }

    public function progressReports()
    {
        return $this->hasMany(ProgressReport::class, 'enrollment_id');
    }
}
