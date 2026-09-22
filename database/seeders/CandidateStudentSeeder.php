<?php

namespace Database\Seeders;

use App\Models\CandidateStudent;
use App\Models\Program;
use Illuminate\Database\Seeder;

class CandidateStudentSeeder extends Seeder
{
    public function run(): void
    {
        $defaultProgram = Program::where('program_name', 'like', '%HSK%')->first()
            ?? Program::first();

        if (!$defaultProgram) {
            $this->command->warn('Belum ada data programs. Jalankan ProgramSeeder dulu sebelum CandidateStudentSeeder.');
            return;
        }

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
            'program_id' => $defaultProgram->id,
            'trial_date' => now()->toDateString(),
            'trial_status' => 'Pending',
            'lead_status' => 'Warm',
        ]);

        // Contoh PASTI ada (bukan cuma andalkan random) buat nge-test
        // alur calon siswa privat dan job ExpireRegistrationFees nanti.
        CandidateStudent::factory()
            ->interestedInPrivate()
            ->count(3)
            ->create();

        CandidateStudent::factory()
            ->registrationFeeExpired()
            ->count(3)
            ->create();

        // Sisanya data acak biasa
        CandidateStudent::factory()
            ->count(33)
            ->create();
    }
}