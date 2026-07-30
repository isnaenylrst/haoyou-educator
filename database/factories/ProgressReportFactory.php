<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProgressReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_type' => fake()->randomElement(['Bulanan', 'Akhir Paket']),
            'report_period' => fake()->monthName() . ' ' . now()->year,
            'file_path' => 'progress_reports/' . fake()->unique()->uuid() . '.pdf',
            'status' => fake()->randomElement(['Draft', 'Submitted', 'Submitted']),
        ];
    }
}
