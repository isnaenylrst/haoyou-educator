<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FollowUpFactory extends Factory
{
    public function definition(): array
    {
        return [
            'followup_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'followup_method' => fake()->randomElement(['WhatsApp', 'Telepon', 'Email']),
            'note' => fake()->sentence(),
            'next_followup' => fake()->optional()->dateTimeBetween('now', '+2 weeks')?->format('Y-m-d'),
            'status' => fake()->randomElement(['Pending', 'Done']),
        ];
    }
}
