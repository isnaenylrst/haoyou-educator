<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        // Kepala Kurikulum (akun umum)
        $user = User::factory()
            ->curriculum()
            ->create([
                'username' => 'curriculum',
                'password' => Hash::make('password'),
            ]);

        Curriculum::create([
            'user_id'    => $user->id,
            'name'       => 'Kepala Kurikulum',
            'phone'      => '081946728327',
            'address'    => 'Malang',
            'join_date'  => '2026-02-02',
            'status'     => 'Active',
        ]);
    }
}