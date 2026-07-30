<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherLeaveFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['Pending', 'Approved', 'Approved', 'Rejected']);
        $isDecided = $status !== 'Pending';

        return [
            'leave_type' => fake()->randomElement(['Sick', 'Permission']),
            'reason' => fake()->sentence(),
            'supporting_document' => fake()->optional()->randomElement(['leaves/' . fake()->uuid() . '.pdf']),
            'status' => $status,
            'approved_at' => $isDecided ? now() : null,
        ];
    }
}
