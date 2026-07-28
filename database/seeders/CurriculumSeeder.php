<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $name = 'Kepala Kurikulum';

        $user = User::factory()
            ->curriculum()
            ->create([
                'username' => 'curriculum',
                'password' => Hash::make('password'),
            ]);

        Curriculum::create([
            'user_id' => $user->id,
            'name' => $name,
            'phone' => '081234567890',
            'address' => 'Malang',
            'specialist' => 'Mandarin Curriculum',
            'join_date' => now()->toDateString(),
            'status' => 'Active',
        ]);
    }
}