<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClassModelFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', '-1 month');
        $status = fake()->randomElement(['Open', 'Running', 'Running', 'Completed']);

        return [
            'class_name' => fake()->randomElement(['Maochong', 'Jianer', 'Hudie', 'Feixiang']) . ' ' . fake()->randomLetter(),
            'delivery_mode' => fake()->randomElement(['Offline', 'Online']),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $status === 'Completed'
                ? fake()->dateTimeBetween($startDate, 'now')->format('Y-m-d')
                : fake()->optional(0.3)->dateTimeBetween('now', '+3 months')?->format('Y-m-d'),
            'capacity' => fake()->numberBetween(4, 8),
            'status' => $status,
        ];
    }

    public function private(): static
    {
        return $this->state(fn () => [
            'program_package_id' => null,
            'level_id' => null,
            'capacity' => fake()->numberBetween(1, 4),
            'delivery_mode' => fake()->randomElement(['Offline', 'Online']),
        ]);
    }
}