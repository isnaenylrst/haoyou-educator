<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;


class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [

            'name' => fake()->name(),

            'phone' => fake()->numerify('08##########'),

            'address' => fake()->address(),

            'specialist' => fake()->randomElement([
                'HSK',
                'Conversation',
                'Kids'
            ]),

            'join_date' => fake()->dateTimeBetween('2024-01-01', 'now')->format('Y-m-d'),

            'training_status' => fake()->randomElement([
                'Training',
                'Passed'
            ]),

            'status' => 'Active',
        ];
    }
}