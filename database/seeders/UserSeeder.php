<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->owner()
            ->create([
                'username' => 'owner',
                'password' => Hash::make('owner123'),
            ]);

        User::factory()
            ->admin()
            ->create([
                'username' => 'admin',
                'password' => Hash::make('admin123'),
            ]);
    }
}