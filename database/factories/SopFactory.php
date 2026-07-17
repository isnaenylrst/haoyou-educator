<?php

namespace Database\Factories;

use App\Models\Curriculum;
use Illuminate\Database\Eloquent\Factories\Factory;

class SopFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [

            'curriculum_id' => Curriculum::factory(),

            'title' => fake()->randomElement([
                'SOP Trial Teaching',
                'SOP Daily Class',
                'SOP Online Class',
                'SOP Hybrid Class',
                'SOP Placement Test'
            ]),

            'description' => fake()->paragraph(),

            'version' => '1.0',

            'file_path' => 'sops/' . fake()->uuid() . '.pdf',

            'status' => fake()->randomElement([
                'draft',
                'published',
                'archived'
            ]),
        ];
    }
}