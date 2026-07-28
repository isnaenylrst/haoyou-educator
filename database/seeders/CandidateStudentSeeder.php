<?php

namespace Database\Seeders;

use App\Models\CandidateStudent;
use Illuminate\Database\Seeder;

class CandidateStudentSeeder extends Seeder
{
    /**
     * DUMMY DATA. Jumlah 40 (harus >= jumlah user Student, lihat StudentSeeder
     * yang mewajibkan tiap student berelasi ke satu candidate_student unik).
     */
    public function run(): void
    {
        CandidateStudent::factory()->count(50)->create();
    }
}
