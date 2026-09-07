<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';

    protected $fillable = [
        'candidate_student_id',
        'user_id',
        'name',
        'points',
        'join_date',
        'status',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    public function candidateStudent()
    {
        return $this->belongsTo(CandidateStudent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments()
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    public function activeEnrollment()
    {
        return $this->hasOne(ClassEnrollment::class)
            ->where('status', 'Active')
            ->latestOfMany('enrollment_date');
    }    

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Semua pembayaran siswa ini, ditarik lewat tabel class_enrollments
    public function payments()
    {
        return $this->hasManyThrough(
            Payment::class,
            ClassEnrollment::class,
            'student_id',   // FK di class_enrollments yang mengarah ke students
            'enrollment_id',// FK di payments yang mengarah ke class_enrollments
            'id',           // local key di students
            'id'            // local key di class_enrollments
        );
    }
 
    // Pembayaran terbaru untuk enrollment yang sedang aktif
    public function latestPayment()
    {
        return $this->payments()->latest('payment_date');
    }
 
    // Umur dihitung dari data calon siswa asalnya (birth_date ada di candidate_students)
    public function getAgeAttribute()
    {
        $birthDate = $this->candidateStudent?->birth_date;
 
        return $birthDate ? \Carbon\Carbon::parse($birthDate)->age : null;
    }

    public function progressReports()
    {
        return $this->hasMany(ProgressReport::class);
    }
}