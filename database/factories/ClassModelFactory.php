<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClassModelFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', '-1 month');

        return [
            'class_name' => fake()->randomElement(['Maochong', 'Jianer', 'Hudie', 'Feixiang']) . ' ' . fake()->randomLetter(),
            'delivery_mode' => fake()->randomElement(['Offline', 'Online']),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => fake()->optional(0.4)->dateTimeBetween($startDate, '+3 months')?->format('Y-m-d'),
            'capacity' => fake()->numberBetween(4, 8),
            'status' => fake()->randomElement(['Open', 'Running', 'Running', 'Completed']),
        ];
    }
}
