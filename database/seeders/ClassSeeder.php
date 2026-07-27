<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $packageIds = DB::table('program_packages')->pluck('id')->toArray();
        $teacherIds = DB::table('teachers')->pluck('id')->toArray();

        $classNames = [
            'Maochong A', 'Jianer A', 'Hudie A', 'Feixiang A',
            'HSK 1 Batch 1', 'HSK 2 Batch 1',
        ];

        foreach ($classNames as $className) {
            $startDate = fake()->dateTimeBetween('-6 months', '-1 month');

            DB::table('classes')->insert([
                'program_package_id' => fake()->randomElement($packageIds),
                'teacher_id' => fake()->randomElement($teacherIds),
                'class_name' => $className,
                'delivery_mode' => fake()->randomElement(['Offline', 'Online']),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => fake()->optional(0.4)->dateTimeBetween($startDate, '+3 months')?->format('Y-m-d'),
                'capacity' => fake()->numberBetween(4, 8),
                'status' => fake()->randomElement(['Open', 'Running', 'Running', 'Completed']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}