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
            'phone'      => '081234567890',
            'address'    => 'Malang',
            'specialist' => 'Mandarin Curriculum',
            'join_date'  => now()->toDateString(),
            'status'     => 'Active',
        ]);

        $fitriUser = User::factory()
            ->curriculum()
            ->create([
                'username' => 'kurikulum',
                'password' => Hash::make('password'),
            ]);

        Curriculum::create([
            'user_id'    => $fitriUser->id,
            'name'       => 'Fitri Maulidah',
            'phone'      => '081946728321',
            'address'    => 'RT 22 RW 04 Dusun Madatan Desa Panggul Kecamatan Panggul Kabupaten Trenggalek',
            'specialist' => 'Mandarin Curriculum',
            'join_date'  => '2026-02-02',
            'status'     => 'Active',
        ]);
    }
}