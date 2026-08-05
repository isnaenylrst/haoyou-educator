<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $meeting = fake()->numberBetween(1, 12);

        $topics = [
            'Perkenalan',
            'Salam',
            'Keluarga',
            'Sekolah',
            'Hobi',
            'Makanan',
            'Minuman',
            'Transportasi',
            'Cuaca',
            'Belanja',
            'Pekerjaan',
            'Percakapan Sehari-hari'
        ];

        $topic = fake()->randomElement($topics);

        return [

            'meeting_number' => $meeting,

            'title' => "Pertemuan {$meeting} - {$topic}",

            'syllabus' => fake()->paragraph(3),

            'material_file_path' =>
                'materials/' . fake()->uuid() . '.pdf',

        ];
    }
}