<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClassEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_date' => fake()->dateTimeBetween('-5 months', 'now')->format('Y-m-d'),
            'status' => fake()->randomElement(['Active', 'Active', 'Completed', 'Cancelled']),
        ];
    }
}
