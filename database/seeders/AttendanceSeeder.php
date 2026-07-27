<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    /**
     * Tiap jurnal mengajar mencatat kehadiran siswa yang terdaftar di kelas
     * terkait. unique(teaching_journal_id, student_id) dijaga karena tiap
     * siswa hanya dicatat sekali per jurnal.
     */
    public function run(): void
    {
        $now = now();

        $journals = DB::table('teaching_journals')->select('id', 'class_id')->get();

        foreach ($journals as $journal) {
            $studentIds = DB::table('class_enrollments')
                ->where('class_id', $journal->class_id)
                ->pluck('student_id');

            foreach ($studentIds as $studentId) {
                DB::table('attendances')->insert([
                    'teaching_journal_id' => $journal->id,
                    'student_id' => $studentId,
                    'status' => fake()->randomElement(['Present', 'Present', 'Present', 'Absent', 'Sick', 'Permission']),
                    'remarks' => fake()->optional()->sentence(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}