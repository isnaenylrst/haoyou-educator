<?php

namespace Database\Factories;

use App\Models\Curriculum;
use App\Models\LessonPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [

            'lesson_plan_id' => LessonPlan::factory(),

            'curriculum_id' => Curriculum::factory(),

            'review_date' => fake()->dateTimeBetween('-30 days', 'now'),

            'status' => fake()->randomElement([
                'pending',
                'approved',
                'revision',
                'rejected',
            ]),

            'comment' => fake()->paragraph(),

        ];
    }
}