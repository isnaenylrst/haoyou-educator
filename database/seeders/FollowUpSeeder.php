<?php

namespace Database\Seeders;

use App\Models\CandidateStudent;
use App\Models\FollowUp;
use App\Models\FollowUpTemplate;
use Illuminate\Database\Seeder;

class FollowUpSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap calon siswa mendapat 1-3 catatan follow up.
     */
    public function run(): void
    {
        $templateIds = FollowUpTemplate::pluck('id')->toArray();
        $candidateStudents = CandidateStudent::all();

        foreach ($candidateStudents as $candidateStudent) {
            FollowUp::factory()
                ->count(fake()->numberBetween(1, 3))
                ->for($candidateStudent, 'followupable')
                ->create([
                    'follow_up_template_id' => fake()->randomElement($templateIds),
                ]);
        }
    }
}