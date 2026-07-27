<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurriculumSeeder extends Seeder
{
    /**
     * Satu baris curriculum untuk tiap user ber-level 'Curriculum' (relasi 1-1).
     */
    public function run(): void
    {
        $now = now();

        $curriculumLevelId = DB::table('levels')->where('nama_level', 'Curriculum')->value('id_level');
        $userIds = DB::table('users')->where('level_id', $curriculumLevelId)->pluck('id');

        foreach ($userIds as $userId) {
            DB::table('curriculum')->insert([
                'user_id' => $userId,
                'name' => fake()->name(),
                'phone' => fake()->numerify('08##########'),
                'address' => fake()->address(),
                'specialist' => fake()->randomElement(['HSK Preparation']),
                'join_date' => fake()->dateTimeBetween('-3 years', '-6 months')->format('Y-m-d'),
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}