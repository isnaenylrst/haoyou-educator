<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FollowUpSeeder extends Seeder
{
    /**
     * Tiap calon siswa mendapat 1-3 catatan follow up.
     */
    public function run(): void
    {
        $now = now();

        $candidateIds = DB::table('candidate_students')->pluck('id');
        $templateIds = DB::table('follow_up_templates')->pluck('id')->toArray();

        foreach ($candidateIds as $candidateId) {
            $count = fake()->numberBetween(1, 3);

            for ($i = 0; $i < $count; $i++) {
                DB::table('follow_ups')->insert([
                    'candidate_student_id' => $candidateId,
                    'follow_up_template_id' => fake()->randomElement($templateIds),
                    'followup_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
                    'followup_method' => fake()->randomElement(['WhatsApp', 'Telepon', 'Email']),
                    'note' => fake()->sentence(),
                    'next_followup' => fake()->optional()->dateTimeBetween('now', '+2 weeks')?->format('Y-m-d'),
                    'status' => fake()->randomElement(['Pending', 'Done']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}