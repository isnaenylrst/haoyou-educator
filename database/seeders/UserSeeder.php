<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([

            'name'=>'Owner',

            'email'=>'owner@haoyou.com',

            'password'=>Hash::make('password'),

            'role'=>'owner',

            'status'=>true

        ]);

        User::create([

            'name'=>'Admin',

            'email'=>'admin@haoyou.com',

            'password'=>Hash::make('password'),

            'role'=>'admin',

            'status'=>true

        ]);

         User::create([
            'name' => 'Kepala Kurikulum',
            'email' => 'kurikulum@haoyou.com',
            'password' => Hash::make('password'),
            'role' => 'curriculum',
            'status' => true,
        ]);

        User::create([
            'name' => 'Guru',
            'email' => 'guru@haoyou.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'status' => true,
        ]);
    }
}