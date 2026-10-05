<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClassEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_date' => fake()->dateTimeBetween('-5 months', 'now')->format('Y-m-d'),
            'status' => fake()->randomElement(['Active', 'Active', 'Completed', 'Cancelled']),
        ];
    }

    /**
     * State eksplisit untuk skenario testing kelas privat —
     * kosongkan program_package_id, isi private_package_id.
     * Tetap butuh class_id/private_package_id diisi manual saat dipanggil
     * (factory tidak tahu instance private_packages/classes mana yang valid).
     */
    public function private(): static
    {
        return $this->state(fn () => [
            'program_package_id' => null,
        ]);
    }

    /**
     * State eksplisit untuk skenario testing kelas reguler.
     */
    public function regular(): static
    {
        return $this->state(fn () => [
            'private_package_id' => null,
        ]);
    }
}