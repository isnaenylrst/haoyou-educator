<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        Teacher::factory()
            ->count(10)
            ->make()
            ->each(function ($teacher) {

                $username = Str::slug($teacher->name, ' ');

                $user = User::factory()
                    ->teacher()
                    ->create([
                        'username' => $username,
                        'password' => Hash::make('password'),
                    ]);

                $teacher->user_id = $user->id;
                $teacher->save();
            });
    }
}