<?php

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'meeting_number' => fake()->numberBetween(1, 3),
            'syllabus' => fake()->paragraph(),
            'material_file_path' => 'materials/' . fake()->unique()->uuid() . '.pdf',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Material $material) {
            if (empty($material->title)) {
                $material->title = 'Pertemuan ' . $material->meeting_number . ' - ' . $this->topicFor($material->meeting_number);
            }
        });
    }

    private function topicFor(int $meetingNumber): string
    {
        // Topik berurutan sesuai progres belajar, bukan acak total
        return match ($meetingNumber) {
            1 => fake()->randomElement(['Perkenalan & Salam', 'Pinyin Dasar']),
            2 => fake()->randomElement(['Angka & Waktu', 'Anggota Keluarga']),
            3 => fake()->randomElement(['Percakapan Sehari-hari', 'Warna & Benda Sekitar']),
            default => fake()->randomElement(['Latihan Percakapan', 'Review Materi']),
        };
    }
}