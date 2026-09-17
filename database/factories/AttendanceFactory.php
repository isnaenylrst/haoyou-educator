<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'attendance_date' => now()->toDateString(),

            'status' => fake()->randomElement([
                'Present',
                'Present',
                'Present',
                'Absent',
                'Sick',
                'Permission',
            ]),

            'note' => fake()->optional()->sentence(),
        ];
    }
}