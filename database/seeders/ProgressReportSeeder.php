<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\ProgressReport;
use Illuminate\Database\Seeder;

class ProgressReportSeeder extends Seeder
{

    public function run(): void
    {
        $enrollments = ClassEnrollment::with('class:id,teacher_id')
            ->whereIn('status', ['Active', 'Completed'])
            ->whereHas('class', fn ($q) => $q->whereNotNull('teacher_id'))
            ->get();

        foreach ($enrollments as $enrollment) {
            $reportCount = $enrollment->status === 'Completed'
                ? fake()->numberBetween(1, 3)
                : 1;

            ProgressReport::factory()
                ->count($reportCount)
                ->create([
                    'student_id' => $enrollment->student_id,
                    'teacher_id' => $enrollment->class->teacher_id,
                    'enrollment_id' => $enrollment->id,
                ]);
        }
    }
}