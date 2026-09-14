<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Database\Seeder;

class ClassEnrollmentSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap siswa yang BELUM punya enrollment didaftarkan ke 1 kelas acak.
     */
    public function run(): void
    {
        $classes = ClassModel::all();

        if ($classes->isEmpty()) {
            $this->command->error('Belum ada data classes. Jalankan seeder kelas dulu.');
            return;
        }

        $studentIds = Student::doesntHave('enrollments')->pluck('id');

        foreach ($studentIds as $studentId) {
            $class = $classes->random();

            ClassEnrollment::create([
                'student_id' => $studentId,
                'class_id' => $class->id,
                'program_package_id' => $class->program_package_id,
                'private_package_id' => null,
                'enrollment_date' => now(),
                'status' => 'Active',
            ]);
        }
    }
}