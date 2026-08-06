<?php

namespace Database\Seeders;

use App\Models\CandidateStudent;
use App\Models\ProgramPackage;
use Illuminate\Database\Seeder;

class CandidateStudentSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPackage = ProgramPackage::where('package_name', 'like', '%HSK%')->first();

        if (!$defaultPackage) {
            $this->command->warn('Belum ada data program_packages. Jalankan ProgramSeeder & ProgramPackageSeeder dulu sebelum CandidateStudentSeeder.');
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
            'program_package' => $defaultPackage->package_name,
            'trial_date' => now()->toDateString(),
            'trial_status' => 'Pending',
            'lead_status' => 'Warm',
        ]);

        CandidateStudent::factory()
            ->count(39)
            ->create();
    }
}