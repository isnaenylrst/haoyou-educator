<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FollowUpFactory extends Factory
{
    public function definition(): array
    {
        $followupDate = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'followup_date' => $followupDate->format('Y-m-d'),
            'followup_method' => fake()->randomElement(['WhatsApp', 'Telepon']),
            'note' => fake()->sentence(),
            'next_followup' => fake()->boolean(70)
                ? (clone $followupDate)->modify('+1 day')->format('Y-m-d')
                : null,
            'status' => fake()->randomElement(['Pending', 'Done']),
        ];
    }
}