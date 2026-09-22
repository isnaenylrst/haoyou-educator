<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use Illuminate\Database\Seeder;

class ClassScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $classes = ClassModel::with(['programPackage:id,duration_minutes', 'privatePackage:id,duration_minutes'])
            ->get();

        foreach ($classes as $class) {
            $durationMinutes = $class->programPackage->duration_minutes
                ?? $class->privatePackage->duration_minutes
                ?? 90;

            $days = collect(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])
                ->shuffle()
                ->take(fake()->numberBetween(1, 2));

            foreach ($days as $day) {
                ClassSchedule::factory()
                    ->forClass($durationMinutes, $class->delivery_mode)
                    ->create([
                        'class_id' => $class->id,
                        'day' => $day,
                    ]);
            }
        }
    }
}