<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'points' => fake()->numberBetween(0,100),

            'join_date' => fake()->dateTimeBetween('2024-01-01', 'now')->format('Y-m-d'),

            'status' => 'Active',
        ];
    }
}