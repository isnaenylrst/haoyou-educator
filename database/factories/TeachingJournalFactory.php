<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TeachingJournalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'is_substitute' => false,
            'substitute_teacher_id' => null,
            'class_status' => fake()->randomElement(['Conducted', 'Conducted', 'Conducted', 'Cancelled', 'Rescheduled']),
            'learning_activities' => fake()->paragraph(),
            'problems' => fake()->optional()->sentence(),
            'solutions' => fake()->optional()->sentence(),
            'results' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function substitute(int $substituteTeacherId): static
    {
        return $this->state([
            'is_substitute' => true,
            'substitute_teacher_id' => $substituteTeacherId,
        ]);
    }
}
