<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Program; 

class CandidateStudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'birth_date' => fake()->dateTimeBetween('-30 years', '-4 years')->format('Y-m-d'),
            'phone' => fake()->numerify('08##########'),
            'parent_name' => fake()->name(),
            'parent_phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'school' => fake()->randomElement(['SD Kartika', 'SMP Negeri 1', 'SMA Negeri 3', null]),
            'source' => fake()->randomElement(['Instagram', 'Referral', 'Website', 'Walk-in', 'TikTok']),
            'allergy' => fake()->randomElement([null, null, 'Kacang', 'Debu']),
            'program_id' => Program::inRandomOrder()->first()->id,
            'trial_date' => fake()->dateTimeBetween('-2 months', '+2 weeks')->format('Y-m-d'),
            'trial_status' => fake()->randomElement(['Pending', 'Completed', 'Cancelled']),
            'lead_status' => fake()->randomElement(['Cold', 'Warm', 'Hot']),
        ];
    }
}
