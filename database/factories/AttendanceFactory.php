<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => fake()->randomElement(['Present', 'Present', 'Present', 'Absent', 'Sick', 'Permission']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
