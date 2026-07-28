<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CandidateStudentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        for ($i = 0; $i < 100; $i++) {
            DB::table('candidate_students')->insert([
                'name' => fake()->name(),
                'gender' => fake()->randomElement(['Male', 'Female']),
                'birth_date' => fake()->dateTimeBetween('-30 years', '-4 years')->format('Y-m-d'),
                'phone' => fake()->numerify('08##########'),
                'parent_name' => fake()->name(),
                'parent_phone' => fake()->numerify('08##########'),
                'address' => fake()->address(),
                'school' => fake()->randomElement(['SD Kartika', 'SMP Negeri 1', 'SMA Negeri 3', null]),
                'source' => fake()->randomElement(['Instagram', 'Referral', 'Website', 'Walk-in', 'TikTok']),
                'allergy' => fake()->randomElement([null, null, 'Kacang', 'Debu']),
                'interested_program' => fake()->randomElement(['Daily Activity - Regular', 'Daily Activity - Private', 'HSK 1', 'HSK 2', 'HSK 3']),
                'trial_date' => fake()->dateTimeBetween('-2 months', '+2 weeks')->format('Y-m-d'),
                'trial_status' => fake()->randomElement(['Pending', 'Completed', 'Cancelled']),
                'lead_status' => fake()->randomElement(['Cold', 'Warm', 'Hot']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}