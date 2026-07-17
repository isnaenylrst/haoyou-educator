<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {

        return [

            'user_id' => User::factory(),

            'title' => fake()->randomElement([
                'Lesson Plan Disetujui',
                'PPT Direvisi',
                'Jadwal Konsultasi Baru',
                'Progress Report Disetujui',
                'Pengajuan Izin Diproses',
                'Surat Resmi Baru',
            ]),

            'message' => fake()->paragraph(),

            'type' => fake()->randomElement([
                'lesson_plan',
                'ppt',
                'consultation',
                'progress_report',
                'leave_request',
                'official_letter',
                'system',
            ]),

            'is_read' => fake()->boolean(),

        ];

    }
}