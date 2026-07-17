<?php

namespace Database\Factories;

use App\Models\Curriculum;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class LearningMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [

            'program_id' => Program::factory(),

            'curriculum_id' => Curriculum::factory(),

            'title' => fake()->randomElement([
                'Pengenalan Hanzi',
                'Percakapan Dasar',
                'Daily Conversation',
                'Listening Exercise',
                'Reading Practice',
                'Chinese Grammar',
                'Speaking Activity'
            ]),

            'level' => fake()->randomElement([
                '1A',
                '1B',
                '2A',
                '2B',
                '3A',
                '3B'
            ]),

            'meeting' => fake()->numberBetween(1, 20),

            'description' => fake()->paragraph(),

            'file_path' => 'learning_materials/' . fake()->uuid() . '.pdf',

            'status' => fake()->randomElement([
                'draft',
                'published',
                'archived'
            ]),
        ];
    }
}