<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends Seeder
{
    /**
     * Satu baris student untuk tiap user ber-level 'Student' (relasi 1-1).
     * SEMUA student WAJIB berelasi ke candidate_students (tidak ada yang null),
     * mensimulasikan bahwa setiap siswa aktif berasal dari calon siswa yang
     * dikonversi. Candidate yang dipakai akan ditandai trial_status='Completed'
     * dan lead_status='Hot' untuk mencerminkan bahwa dia sudah jadi siswa.
     *
     * PENTING: jumlah candidate_students (lihat CandidateStudentSeeder) harus
     * >= jumlah user ber-level Student, supaya tidak ada candidate yang
     * dipakai dua kali.
     */
    public function run(): void
    {
        $now = now();

        $studentLevelId = DB::table('levels')->where('nama_level', 'Student')->value('id_level');
        $userIds = DB::table('users')->where('level_id', $studentLevelId)->pluck('id')->toArray();

        $candidateIds = DB::table('candidate_students')->pluck('id')->shuffle()->toArray();

        if (count($candidateIds) < count($userIds)) {
            throw new \RuntimeException(
                'Jumlah candidate_students (' . count($candidateIds) . ') kurang dari jumlah user Student (' . count($userIds) . '). '
                . 'Tambah jumlah data di CandidateStudentSeeder supaya setiap student punya candidate yang unik.'
            );
        }

        foreach ($userIds as $index => $userId) {
            $candidateId = $candidateIds[$index];

            DB::table('students')->insert([
                'candidate_student_id' => $candidateId,
                'user_id' => $userId,
                'name' => fake()->name(),
                'points' => fake()->numberBetween(0, 500),
                'join_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                'status' => fake()->randomElement(['Active', 'Active', 'Active', 'Inactive', 'Graduated']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('candidate_students')
                ->where('id', $candidateId)
                ->update([
                    'trial_status' => 'Completed',
                    'lead_status' => 'Hot',
                    'updated_at' => $now,
                ]);
        }
    }
}