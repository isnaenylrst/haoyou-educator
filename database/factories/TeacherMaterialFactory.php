<?php

namespace Database\Factories;

use Database\Seeders\Support\DummyFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherMaterialFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['Pending', 'Approved', 'Approved', 'Rejected']);
        $isReviewed = $status !== 'Pending';

        return [
            'ppt_title' => 'PPT - ' . fake()->words(3, true),
            'ppt_file_path' => DummyFile::store('ppt', 'sample.pptx'),
            'status' => $status,
            'review_note' => $isReviewed ? fake()->sentence() : null,
            'approved_at' => $isReviewed ? now() : null,
        ];
    }
}