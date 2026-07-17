<?php

namespace Database\Factories;

use App\Models\Curriculum;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class SyllabusFactory extends Factory
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
                'Silabus Mandarin Dasar',
                'Silabus Daily Regular',
                'Silabus HSK 1',
                'Silabus Speaking Class',
                'Silabus Reading Class'
            ]),

            'level' => fake()->randomElement([
                '1A',
                '1B',
                '2A',
                '2B',
                '3A',
                '3B'
            ]),

            'description' => fake()->paragraph(),

            'status' => fake()->randomElement([
                'draft',
                'published',
                'archived'
            ]),

        ];
    }
}