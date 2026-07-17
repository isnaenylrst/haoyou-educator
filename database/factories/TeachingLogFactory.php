<?php

namespace Database\Factories;

use App\Models\ClassSchedule;
use App\Models\LessonPlan;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeachingLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [

            'class_schedule_id' => ClassSchedule::factory(),

            'teacher_id' => Teacher::factory(),

            'lesson_plan_id' => LessonPlan::factory(),

            'summary' => fake()->paragraph(),

            'obstacle' => fake()->optional()->sentence(),

            'follow_up' => fake()->optional()->sentence(),

        ];
    }
}