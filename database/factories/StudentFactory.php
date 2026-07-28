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

            'name' => fake()->name(),

            'points' => fake()->numberBetween(0,100),

            'join_date' => fake()->date(),

            'status' => 'Active',
        ];
    }
}