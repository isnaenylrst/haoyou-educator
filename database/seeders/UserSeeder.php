<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Membuat user untuk tiap level: 1 Admin, 2 Curriculum, 5 Teacher, 15 Student.
     * Password default: 'password' (di-hash). Username dibuat unik dari nama+angka.
     */
    public function run(): void
    {
        $now = now();

        $levelIds = DB::table('levels')->pluck('id_level', 'nama_level');

        $plan = [
            'Admin' => 1,
            'Curriculum' => 1,
            'Teacher' => 10,
            'Student' => 30,
        ];

        foreach ($plan as $levelName => $count) {
            for ($i = 1; $i <= $count; $i++) {
                DB::table('users')->insert([
                    'level_id' => $levelIds[$levelName],
                    'username' => strtolower($levelName) . $i . '_' . fake()->unique()->userName(),
                    'password' => Hash::make('password'),
                    'status' => 'Active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}