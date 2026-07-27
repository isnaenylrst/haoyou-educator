<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('programs')->insert([
            [
                'program_name' => 'Daily Activity',
                'description' => 'Program belajar bahasa Mandarin sehari-hari dengan metode kinestetik, sensory, motorik (Game Based Learning) mencakup tema Business, Daily Conversation, School Tutorial, dan YCT. Tersedia kelas Regular maupun Private.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'program_name' => 'HSK',
                'description' => 'Program persiapan ujian HSK (Hanyu Shuiping Kaoshi) level 1 sampai 6, kelas group dengan kurikulum bertahap sesuai standar HSK.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}