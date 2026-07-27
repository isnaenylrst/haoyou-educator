<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassEnrollmentSeeder extends Seeder
{
    /**
     * Tiap siswa didaftarkan ke 1 kelas secara acak.
     */
    public function run(): void
    {
        $now = now();

        $studentIds = DB::table('students')->pluck('id');
        $classIds = DB::table('classes')->pluck('id')->toArray();

        foreach ($studentIds as $studentId) {
            DB::table('class_enrollments')->insert([
                'student_id' => $studentId,
                'class_id' => fake()->randomElement($classIds),
                'enrollment_date' => fake()->dateTimeBetween('-5 months', 'now')->format('Y-m-d'),
                'status' => fake()->randomElement(['Active', 'Active', 'Completed', 'Cancelled']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}