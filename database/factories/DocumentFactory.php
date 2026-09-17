<?php

namespace Database\Factories;

use App\Models\ProgramLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    private static ?array $programLevelIds = null;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'document_type' => fake()->randomElement(['CV', 'Photo', 'Teacher Certificate', 'Agreement', 'SOP', 'Teacher Leave Letter', 'Other']),
            'description' => fake()->optional()->sentence(),
            'file_path' => 'documents/' . fake()->unique()->uuid() . '.pdf',
            'visibility' => fake()->randomElement(['Private', 'Teacher', 'Student', 'Public']),
        ];
    }

    public function certificate(?int $programLevelId = null): static
    {
        $levelIds = $this->programLevelIds();

        return $this->state(fn () => [
            'document_type' => 'Certificate',
            'program_level_id' => $programLevelId
                ?? (empty($levelIds) ? null : fake()->randomElement($levelIds)),
            'visibility' => 'Student',
            'file_path' => 'documents/certificates/' . fake()->unique()->uuid() . '.pdf',
        ]);
    }

    public function privateMaterial(): static
    {
        return $this->state(fn () => [
            'document_type' => 'Other',
            'title' => 'Materi Privat: ' . fake()->randomElement([
                'Percakapan Bisnis',
                'Persiapan Wawancara Kerja',
                'Kosakata Traveling',
                'Menulis Hanzi Dasar',
            ]),
            'visibility' => 'Student',
            'program_level_id' => null,
        ]);
    }

    private function programLevelIds(): array
    {
        if (self::$programLevelIds === null) {
            self::$programLevelIds = ProgramLevel::pluck('id')->toArray();
        }

        return self::$programLevelIds;
    }
}