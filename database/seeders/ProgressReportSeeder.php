<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgressReportSeeder extends Seeder
{
    /**
     * Tiap enrollment mendapat 1 laporan progres, ditulis oleh guru pengajar
     * kelas terkait.
     */
    public function run(): void
    {
        $now = now();

        $enrollments = DB::table('class_enrollments')
            ->join('classes', 'classes.id', '=', 'class_enrollments.class_id')
            ->select('class_enrollments.id as enrollment_id', 'class_enrollments.student_id', 'classes.teacher_id')
            ->whereNotNull('classes.teacher_id')
            ->get();

        foreach ($enrollments as $enrollment) {
            DB::table('progress_reports')->insert([
                'student_id' => $enrollment->student_id,
                'teacher_id' => $enrollment->teacher_id,
                'enrollment_id' => $enrollment->enrollment_id,
                'report_type' => fake()->randomElement(['Bulanan', 'Akhir Paket']),
                'report_period' => fake()->monthName() . ' ' . now()->year,
                'file_path' => 'progress_reports/report_' . $enrollment->enrollment_id . '.pdf',
                'status' => fake()->randomElement(['Draft', 'Submitted', 'Submitted']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}