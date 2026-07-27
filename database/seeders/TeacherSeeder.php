<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherSeeder extends Seeder
{
    /**
     * Satu baris teacher untuk tiap user ber-level 'Teacher' (relasi 1-1).
     */
    public function run(): void
    {
        $now = now();

        $teacherLevelId = DB::table('levels')->where('nama_level', 'Teacher')->value('id_level');
        $userIds = DB::table('users')->where('level_id', $teacherLevelId)->pluck('id');

        foreach ($userIds as $userId) {
            DB::table('teachers')->insert([
                'user_id' => $userId,
                'name' => fake()->name(),
                'phone' => fake()->numerify('08##########'),
                'address' => fake()->address(),
                'specialist' => fake()->randomElement(['HSK', 'Business Mandarin', 'Daily Conversation', 'School Tutorial', 'YCT']),
                'join_date' => fake()->dateTimeBetween('-3 years', '-3 months')->format('Y-m-d'),
                'training_status' => fake()->randomElement(['Training', 'Passed', 'Passed', 'Failed']),
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}