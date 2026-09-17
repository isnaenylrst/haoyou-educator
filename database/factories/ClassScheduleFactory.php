<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClassScheduleFactory extends Factory
{
    public function definition(): array
    {
        return $this->buildSlot(90, null);
    }

    public function forClass(int $durationMinutes, ?string $deliveryMode = null): static
    {
        return $this->state(fn () => $this->buildSlot($durationMinutes, $deliveryMode));
    }

    private function buildSlot(int $durationMinutes, ?string $deliveryMode): array
    {
        $startHour = fake()->numberBetween(9, 18);
        $startMinute = fake()->randomElement([0, 30]);

        $endTotalMinutes = ($startHour * 60) + $startMinute + $durationMinutes;

        return [
            'day' => fake()->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']),
            'start_time' => sprintf('%02d:%02d:00', $startHour, $startMinute),
            'end_time' => sprintf('%02d:%02d:00', intdiv($endTotalMinutes, 60), $endTotalMinutes % 60),
            'room' => $this->roomFor($deliveryMode),
        ];
    }

    private function roomFor(?string $deliveryMode): string
    {
        return match ($deliveryMode) {
            'Online' => 'Online',
            'Offline' => fake()->randomElement(['Ruang A', 'Ruang B', 'Ruang C']),
            default => fake()->randomElement(['Ruang A', 'Ruang B', 'Ruang C', 'Online']),
        };
    }
}