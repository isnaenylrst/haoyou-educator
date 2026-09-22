<?php

namespace Database\Seeders;

use App\Models\CandidateStudent;
use App\Models\CandidateStudentAvailableSchedule;
use Illuminate\Database\Seeder;

class CandidateStudentAvailableScheduleSeeder extends Seeder
{
    /**
     * DUMMY DATA — tiap calon siswa punya 1-2 slot jadwal ketersediaan,
     * dengan HARI YANG BERBEDA per slot (tidak masuk akal kalau hari yang
     * sama muncul dua kali), dan jam disesuaikan usia:
     * - Anak-anak (Maochong/Jianer, <10 tahun): sore-malam (sepulang sekolah)
     * - Dewasa: lebih fleksibel, siang-malam
     */
    public function run(): void
    {
        $candidates = CandidateStudent::select('id', 'birth_date')->get();

        foreach ($candidates as $candidate) {
            $isChild = $candidate->birth_date && $candidate->birth_date->age < 10;

            $days = collect(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']);
            $count = fake()->numberBetween(1, 2);
            $chosenDays = $days->shuffle()->take($count);

            foreach ($chosenDays as $day) {
                CandidateStudentAvailableSchedule::factory()
                    ->when($isChild, fn ($factory) => $factory->childHours(), fn ($factory) => $factory->adultHours())
                    ->create([
                        'candidate_student_id' => $candidate->id,
                        'day' => $day,
                    ]);
            }
        }
    }
}