<?php

namespace Database\Factories;

use App\Models\Curriculum;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [

            'teacher_id' => Teacher::factory(),

            'curriculum_id' => Curriculum::factory(),

            'consultation_date' => fake()->dateTimeBetween('now', '+2 months'),

            'topic' => fake()->randomElement([
                'Review Lesson Plan',
                'Review PPT',
                'Evaluasi Mengajar',
                'Pendampingan Guru Baru',
                'Konsultasi Materi Pembelajaran',
            ]),

            'result' => fake()->optional()->paragraph(),

            'status' => fake()->randomElement([
                'scheduled',
                'completed',
                'cancelled',
            ]),

        ];
    }
}