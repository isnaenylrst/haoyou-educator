<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Curriculum;
use App\Models\User;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'kurikulum@haoyou.com')->first();

        if ($user) {

            Curriculum::create([

                'user_id' => $user->id,

                'nama' => $user->name,

                'status' => true,

            ]);

        }
    }
}
