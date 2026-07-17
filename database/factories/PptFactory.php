<?php

namespace Database\Factories;

use App\Models\LessonPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PptFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [

            'lesson_plan_id' => LessonPlan::factory(),

            'title' => fake()->randomElement([
                'PPT Hanzi Dasar',
                'PPT Daily Conversation',
                'PPT Reading Practice',
                'PPT Listening Class',
                'PPT Grammar Mandarin'
            ]),

            'file_path' => 'ppts/' . fake()->uuid() . '.pptx',

            'status' => fake()->randomElement([
                'draft',
                'submitted',
                'approved',
                'revision'
            ]),

        ];
    }
}