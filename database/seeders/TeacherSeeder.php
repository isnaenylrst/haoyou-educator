<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Teacher::create([
            'user_id' => 4,
            'nama' => 'Guru Mandarin 1',
            'no_hp' => '081234567890',
            'spesialisasi' => 'Daily Regular',
            'status' => true,
        ]);
    }
}
