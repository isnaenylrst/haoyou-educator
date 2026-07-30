<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClassScheduleFactory extends Factory
{
    public function definition(): array
    {
        $startHour = fake()->numberBetween(9, 18);

        return [
            'day' => fake()->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:30:00', $startHour + 1),
            'room' => fake()->randomElement(['Ruang A', 'Ruang B', 'Ruang C', 'Online']),
        ];
    }
}
