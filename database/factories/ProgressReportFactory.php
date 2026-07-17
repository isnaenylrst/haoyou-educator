<?php

namespace Database\Factories;

use App\Models\CourseClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TemplateProgressReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgressReportFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [

            'student_id' => Student::factory(),

            'class_id' => CourseClass::factory(),

            'teacher_id' => Teacher::factory(),

            'template_progress_report_id' => TemplateProgressReport::factory(),

            'report_date' => fake()->date(),

            'communication' => fake()->numberBetween(70,100),

            'confidence' => fake()->numberBetween(70,100),

            'listening' => fake()->numberBetween(70,100),

            'reading' => fake()->numberBetween(70,100),

            'writing' => fake()->numberBetween(70,100),

            'behavior' => fake()->numberBetween(80,100),

            'homework' => fake()->sentence(),

            'teacher_notes' => fake()->paragraph(),

            'pdf_file' => 'progress_reports/'.fake()->uuid().'.pdf',

            'status' => fake()->randomElement([
                'draft',
                'submitted',
                'approved'
            ]),

        ];
    }
}