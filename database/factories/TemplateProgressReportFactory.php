<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateProgressReportFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $category = fake()->randomElement([
            'Daily Regular',
            'HSK',
            'Private',
            'Bisnis',
            'Mandarin Tradisional / TOCFL'
        ]);

        return [

            'name' => 'Template ' . $category,

            'category' => $category,

            'template_file' => 'templates/' . fake()->uuid() . '.pdf',

            'description' => fake()->paragraph(),

            'status' => fake()->randomElement([
                'active',
                'inactive'
            ]),

        ];
    }
}