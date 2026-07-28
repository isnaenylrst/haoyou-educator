<?php

namespace Database\Seeders;

use App\Models\CandidateStudent;
use App\Models\CandidateStudentAvailableSchedule;
use Illuminate\Database\Seeder;

class CandidateStudentAvailableScheduleSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap calon siswa punya 1-2 slot jadwal ketersediaan.
     */
    public function run(): void
    {
        $candidateIds = CandidateStudent::pluck('id');

        foreach ($candidateIds as $candidateId) {
            CandidateStudentAvailableSchedule::factory()
                ->count(fake()->numberBetween(1, 2))
                ->create(['candidate_student_id' => $candidateId]);
        }
    }
}
