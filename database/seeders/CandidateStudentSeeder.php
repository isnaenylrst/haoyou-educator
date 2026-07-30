<?php

namespace Database\Seeders;

use App\Models\CandidateStudent;
use Illuminate\Database\Seeder;

class CandidateStudentSeeder extends Seeder
{
    public function run(): void
    {
        // Candidate Student tetap
        CandidateStudent::create([
            'name' => 'Isnaeny Larassati',
            'gender' => 'Female',
            'birth_date' => '2004-01-01',
            'phone' => '081234567890',
            'parent_name' => 'Orang Tua Isnaeny',
            'parent_phone' => '081234567891',
            'address' => 'Malang',
            'school' => 'Politeknik Negeri Malang',
            'source' => 'Instagram',
            'allergy' => null,
            'interested_program' => 'HSK 1',
            'trial_date' => now()->toDateString(),
            'trial_status' => 'Pending',
            'lead_status' => 'Warm',
        ]);

        // Dummy data
        CandidateStudent::factory()
            ->count(39)
            ->create();
    }
}