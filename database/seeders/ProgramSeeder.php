<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        Program::create([
            'program_name' => 'Daily Activity',
            'description' => 'Program belajar bahasa Mandarin reguler dengan kurikulum Haoyou Educator, kinestetik & game based learning.',
        ]);

        Program::create([
            'program_name' => 'HSK',
            'description' => 'Program persiapan dan pembelajaran Hanyu Shuiping Kaoshi (HSK) level 1-6.',
        ]);
    }
}