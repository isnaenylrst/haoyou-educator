<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\ProgressReport;
use Illuminate\Database\Seeder;

class ProgressReportSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap enrollment mendapat 1 laporan progres, ditulis oleh
     * guru pengajar kelas terkait.
     */
    public function run(): void
    {
        $enrollments = ClassEnrollment::join('classes', 'classes.id', '=', 'class_enrollments.class_id')
            ->select('class_enrollments.id as enrollment_id', 'class_enrollments.student_id', 'classes.teacher_id')
            ->whereNotNull('classes.teacher_id')
            ->get();

        foreach ($enrollments as $enrollment) {
            ProgressReport::factory()->create([
                'student_id' => $enrollment->student_id,
                'teacher_id' => $enrollment->teacher_id,
                'enrollment_id' => $enrollment->enrollment_id,
            ]);
        }
    }
}
