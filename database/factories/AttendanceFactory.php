<?php

namespace Database\Factories;

use App\Models\ClassSchedule;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [

            'class_schedule_id' => ClassSchedule::factory(),

            'student_id' => Student::factory(),

            'teacher_id' => Teacher::factory(),

            'status' => fake()->randomElement([
                'present',
                'absent',
                'late',
                'permission',
            ]),

            'notes' => fake()->optional()->sentence(),

        ];
    }
}