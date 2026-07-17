<?php

namespace Database\Factories;

use App\Models\ClassSchedule;
use App\Models\LearningMaterial;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [

            'class_schedule_id' => ClassSchedule::factory(),

            'teacher_id' => Teacher::factory(),

            'learning_material_id' => LearningMaterial::factory(),

            'title' => fake()->randomElement([
                'Lesson Plan Pertemuan 1',
                'Lesson Plan Hanzi Dasar',
                'Lesson Plan Daily Conversation',
                'Lesson Plan Listening',
                'Lesson Plan Reading'
            ]),

            'objective' => fake()->paragraph(),

            'activity' => fake()->paragraphs(3, true),

            'assessment' => fake()->sentence(),

            'status' => fake()->randomElement([
                'draft',
                'submitted',
                'approved',
                'revision'
            ]),
        ];
    }
}