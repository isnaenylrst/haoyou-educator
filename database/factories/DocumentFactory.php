<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'document_type' => fake()->randomElement(['CV', 'Photo', 'Teacher Certificate', 'Agreement', 'SOP', 'Teacher Leave Letter', 'Other']),
            'description' => fake()->optional()->sentence(),
            'file_path' => 'documents/' . fake()->unique()->uuid() . '.pdf',
            'visibility' => fake()->randomElement(['Private', 'Teacher', 'Student', 'Public']),
        ];
    }
}
