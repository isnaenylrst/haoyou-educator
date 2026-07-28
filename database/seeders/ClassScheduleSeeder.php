<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassScheduleSeeder extends Seeder
{
    /**
     * Tiap kelas punya 1-2 jadwal pertemuan per minggu.
     */
    public function run(): void
    {
        $now = now();
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        $classIds = DB::table('classes')->pluck('id');

        foreach ($classIds as $classId) {
            $sessions = fake()->numberBetween(1, 2);

            for ($i = 0; $i < $sessions; $i++) {
                $startHour = fake()->numberBetween(9, 18);

                DB::table('class_schedules')->insert([
                    'class_id' => $classId,
                    'day' => fake()->randomElement($days),
                    'start_time' => sprintf('%02d:00:00', $startHour),
                    'end_time' => sprintf('%02d:30:00', $startHour + 1),
                    'room' => fake()->randomElement(['Ruang A', 'Ruang B', 'Ruang C', 'Online']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}