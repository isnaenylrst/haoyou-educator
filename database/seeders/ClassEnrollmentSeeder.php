<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Database\Seeder;

class ClassEnrollmentSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap siswa didaftarkan ke 1 kelas acak.
     */
    public function run(): void
    {
        $studentIds = Student::pluck('id');
        $classIds = ClassModel::pluck('id')->toArray();

        foreach ($studentIds as $studentId) {
            ClassEnrollment::factory()->create([
                'student_id' => $studentId,
                'class_id' => fake()->randomElement($classIds),
            ]);
        }
    }
}
