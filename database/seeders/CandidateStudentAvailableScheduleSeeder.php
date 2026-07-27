<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CandidateStudentAvailableScheduleSeeder extends Seeder
{
    /**
     * Tiap calon siswa punya 1-2 slot jadwal ketersediaan.
     */
    public function run(): void
    {
        $now = now();
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        $candidateIds = DB::table('candidate_students')->pluck('id');

        foreach ($candidateIds as $candidateId) {
            $slots = fake()->numberBetween(1, 2);

            for ($i = 0; $i < $slots; $i++) {
                $startHour = fake()->numberBetween(9, 18);

                DB::table('candidate_student_available_schedules')->insert([
                    'candidate_student_id' => $candidateId,
                    'day' => fake()->randomElement($days),
                    'start_time' => sprintf('%02d:00:00', $startHour),
                    'end_time' => sprintf('%02d:30:00', $startHour + 1),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}