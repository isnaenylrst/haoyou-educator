<?php

namespace Database\Factories;

use App\Models\ProgramLevel;
use Database\Seeders\Support\DummyFile;
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

            // Pakai closure supaya file HANYA dibuat kalau nilai ini tidak di-override oleh state
            // (mis. certificate()). Kalau tidak, file yatim akan tercipta di storage.
            'file_path' => fn (array $attributes) => $attributes['document_type'] === 'Photo'
                ? DummyFile::store('documents', 'sample.jpg')
                : DummyFile::store('documents', 'sample.pdf'),

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
            'file_path' => fn () => DummyFile::store('documents/certificates', 'sample.pdf'),
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