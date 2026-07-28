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
        // =========================
        // Student Tetap
        // =========================

        $candidate = CandidateStudent::where('phone' ,'081234567890')->first();

        $user = User::factory()
            ->student()
            ->create([
                'username' => 'student',
                'password' => Hash::make('student123'),
            ]);

        Student::factory()->create([
            'candidate_student_id' => $candidate->id,
            'user_id' => $user->id,
            'name' => $candidate->name,
        ]);

        $candidate->update([
            'trial_status' => 'Completed',
            'lead_status' => 'Hot',
        ]);

        // =========================
        // Student Dummy
        // =========================

        $candidates = CandidateStudent::where('id', '!=', $candidate->id)
            ->inRandomOrder()
            ->take(14)
            ->get();

        foreach ($candidates as $candidate) {

            $username = Str::slug($candidate->name, '_');

            if (User::where('username', $username)->exists()) {
                $username .= fake()->numberBetween(100, 999);
            }

            $user = User::factory()
                ->student()
                ->create([
                    'username' => $username,
                    'password' => Hash::make('password'),
                ]);

            Student::factory()->create([
                'candidate_student_id' => $candidate->id,
                'user_id' => $user->id,
                'name' => $candidate->name,
            ]);

            $candidate->update([
                'trial_status' => 'Completed',
                'lead_status' => 'Hot',
            ]);
        }
    }
}