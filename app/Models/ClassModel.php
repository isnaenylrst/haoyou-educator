<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'program_package_id',
        'private_package_id',
        'level_id',
        'teacher_id',
        'class_name',
        'delivery_mode',
        'start_date',
        'end_date',
        'capacity',
        'status',
        'teacher_response',
        'teacher_response_note',
        'teacher_responded_at',
    ];

    protected $casts = [
        'start_date'           => 'date',
        'end_date'             => 'date',
        'capacity'             => 'integer',
        'teacher_responded_at' => 'datetime',
    ];

    /** Status enrollment yang memakai kursi di kelas ini. */
    public const SEAT_STATUSES = ['Active', 'Waiting Class'];

    protected static function booted()
    {
        // Ganti guru pada kelas yang sudah ada -> guru baru harus konfirmasi ulang.
        static::updating(function (ClassModel $class) {
            if ($class->isDirty('teacher_id') && !$class->isDirty('teacher_response')) {
                $class->teacher_response = 'Pending';
                $class->teacher_response_note = null;
                $class->teacher_responded_at = null;
            }
        });
    }

    /* ---------------------------------------------------------- */
    /*  Relasi                                                     */
    /* ---------------------------------------------------------- */

    public function programPackage()
    {
        return $this->belongsTo(ProgramPackage::class);
    }

    // Kelas privat mengisi ini, kelas reguler mengisi programPackage()
    public function privatePackage()
    {
        return $this->belongsTo(PrivatePackage::class);
    }

    public function level()
    {
        return $this->belongsTo(ProgramLevel::class, 'level_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function enrollments()
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id');
    }

    /** Pertemuan bertanggal (rencana), urut nomor pertemuan. */
    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'class_id')->orderBy('session_number');
    }

    /**
     * Pola mingguan yang masih berlaku (effective_until kosong).
     * Kode lama yang memakai $class->schedules otomatis tidak melihat pola yang sudah ditutup.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'class_id')->whereNull('effective_until');
    }

    /** Seluruh riwayat pola mingguan, termasuk yang sudah ditutup. */
    public function allSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'class_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class, 'class_id');
    }

    /* ---------------------------------------------------------- */
    /*  Kapasitas & kuorum (dari min_students / max_students paket) */
    /* ---------------------------------------------------------- */

    /** Paket yang dipakai kelas ini (privat atau reguler). */
    public function package()
    {
        return $this->private_package_id ? $this->privatePackage : $this->programPackage;
    }

    public function enrolledCount(): int
    {
        return $this->enrollments()->whereIn('status', self::SEAT_STATUSES)->count();
    }

    /** Minimal siswa agar kelas boleh mulai (min_students paket, minimal 1). */
    public function minStudents(): int
    {
        return (int) ($this->package()?->min_students ?: 1);
    }

    public function seatsLeft(): int
    {
        return max((int) $this->capacity - $this->enrolledCount(), 0);
    }

    public function isFull(): bool
    {
        return $this->seatsLeft() === 0;
    }

    /** Kuorum terpenuhi: jumlah siswa sudah mencapai minimal paket. */
    public function isReadyToStart(): bool
    {
        return $this->enrolledCount() >= $this->minStudents();
    }

    /** Berapa siswa lagi yang dibutuhkan sampai kuorum (0 kalau sudah terpenuhi). */
    public function studentsNeeded(): int
    {
        return max($this->minStudents() - $this->enrolledCount(), 0);
    }
}