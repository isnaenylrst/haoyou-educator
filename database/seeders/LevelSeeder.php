<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('levels')->insert([
            [
                'id_level' => 1,
                'nama_level' => 'Owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_level' => 2,
                'nama_level' => 'Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_level' => 3,
                'nama_level' => 'Curriculum',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_level' => 4,
                'nama_level' => 'Teacher',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_level' => 5,
                'nama_level' => 'Student',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}