<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    public function definition(): array
    {
        $meetingNumber = fake()->numberBetween(1, 3);
        $topic = fake()->randomElement(['Perkenalan & Salam', 'Angka & Waktu', 'Percakapan Sehari-hari']);

        return [
            'meeting_number' => $meetingNumber,
            'title' => 'Pertemuan ' . $meetingNumber . ' - ' . $topic,
            'syllabus' => fake()->paragraph(),
            'material_file_path' => 'materials/' . fake()->unique()->uuid() . '.pdf',
        ];
    }
}
