<?php

namespace Database\Seeders;

use App\Models\ReviewLog;
use Illuminate\Database\Seeder;

class ReviewLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        ReviewLog::insert([

            [

                'lesson_plan_id' => 1,
                'curriculum_id' => 1,
                'review_date' => now()->subDays(3),
                'status' => 'revision',
                'comment' => 'Tambahkan aktivitas speaking pada sesi pembukaan.',
                'created_at' => now(),
                'updated_at' => now(),

            ],

            [

                'lesson_plan_id' => 1,
                'curriculum_id' => 1,
                'review_date' => now()->subDays(1),
                'status' => 'approved',
                'comment' => 'Lesson Plan sudah sesuai dengan standar kurikulum.',
                'created_at' => now(),
                'updated_at' => now(),

            ],

            [

                'lesson_plan_id' => 2,
                'curriculum_id' => 2,
                'review_date' => now(),
                'status' => 'pending',
                'comment' => 'Menunggu proses review dari Kepala Kurikulum.',
                'created_at' => now(),
                'updated_at' => now(),

            ],

        ]);

    }
}