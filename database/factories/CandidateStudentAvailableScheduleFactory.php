<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateStudentAvailableScheduleFactory extends Factory
{
    public function definition(): array
    {
        // Default: rentang umum kalau tidak dipakai lewat state childHours()/adultHours()
        return $this->slotBetween(9, 20);
    }

    /**
     * Anak-anak (Maochong/Jianer, biasanya masih sekolah/TK) realistisnya
     * baru available sepulang sekolah — sore sampai awal malam.
     */
    public function childHours(): static
    {
        return $this->state(fn () => $this->slotBetween(13, 19));
    }

    /**
     * Dewasa (Hudie/Feixiang) lebih fleksibel tapi tetap condong ke luar
     * jam kerja (siang untuk yang WFH/kuliah, atau malam sepulang kerja).
     */
    public function adultHours(): static
    {
        return $this->state(fn () => $this->slotBetween(10, 21));
    }

    private function slotBetween(int $earliest, int $latestStart): array
    {
        $startHour = fake()->numberBetween($earliest, $latestStart - 1);

        return [
            'day' => fake()->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:30:00', $startHour + 1),
        ];
    }
}