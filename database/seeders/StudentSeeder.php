<?php

namespace Database\Seeders;

use App\Models\CandidateStudent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $candidateIds = CandidateStudent::pluck('id')->shuffle()->values();

        Student::factory()
            ->count(30)
            ->make()
            ->each(function ($student, $index) use ($candidateIds) {

                $username = Str::slug($student->name, ' ');

                // Jika ada nama yang sama, tambahkan angka
                if (User::where('username', $username)->exists()) {
                    $username .= fake()->numberBetween(100,999);
                }

                $user = User::factory()
                    ->student()
                    ->create([
                        'username' => $username,
                        'password' => Hash::make('password'),
                    ]);

                $student->user_id = $user->id;

                $student->candidate_student_id = $candidateIds[$index];

                $student->save();

                CandidateStudent::find($candidateIds[$index])
                    ->update([
                        'trial_status' => 'Completed',
                        'lead_status' => 'Hot',
                    ]);
            });
    }
}