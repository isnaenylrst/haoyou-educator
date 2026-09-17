<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Database\Seeder;

class ClassEnrollmentSeeder extends Seeder
{
    /**
     * DUMMY DATA — tiap siswa yang BELUM punya enrollment didaftarkan ke
     * 1 kelas acak yang MASIH ADA SLOT KOSONG (menghormati classes.capacity).
     * Identitas paket (program_package_id / private_package_id) diambil
     * langsung dari kelas yang dipilih — otomatis benar untuk kelas reguler
     * MAUPUN privat, tidak perlu dibedakan manual.
     */
    public function run(): void
    {
        $classes = ClassModel::withCount([
            'enrollments as active_enrollments_count' => fn ($q) => $q->whereIn('status', ['Active', 'Completed']),
        ])->get();

        if ($classes->isEmpty()) {
            $this->command->error('Belum ada data classes. Jalankan seeder kelas dulu.');
            return;
        }

        $studentIds = Student::doesntHave('enrollments')->pluck('id');

        foreach ($studentIds as $studentId) {
            $availableClasses = $classes->filter(
                fn ($class) => $class->active_enrollments_count < $class->capacity
            );

            if ($availableClasses->isEmpty()) {
                $this->command->warn('Semua kelas sudah penuh, sisa siswa tidak ikut di-enroll.');
                break;
            }

            $class = $availableClasses->random();

            // enrollment_date HARUS di antara kelas mulai dan hari ini —
            // supaya konsisten dengan filter di AttendanceSeeder
            // (enrollment_date <= session_date). Kalau kelas belum mulai
            // (start_date di masa depan), pakai hari ini sebagai batas bawah.
            $rangeStart = $class->start_date->isFuture() ? now() : $class->start_date;
            $enrollmentDate = fake()->dateTimeBetween($rangeStart, 'now')->format('Y-m-d');

            ClassEnrollment::factory()->create([
                'student_id' => $studentId,
                'class_id' => $class->id,
                'program_package_id' => $class->program_package_id,
                'private_package_id' => $class->private_package_id,
                'enrollment_date' => $enrollmentDate,
            ]);

            $class->active_enrollments_count++;
        }
    }
}