<?php

namespace Database\Seeders;

use App\Models\Ppt;
use Illuminate\Database\Seeder;

class PptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ppt::insert([

            [
                'lesson_plan_id' => 1,
                'title' => 'PPT Hanzi Dasar',
                'file_path' => 'ppts/hanzi_dasar.pptx',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'lesson_plan_id' => 1,
                'title' => 'PPT Daily Conversation',
                'file_path' => 'ppts/daily_conversation.pptx',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'lesson_plan_id' => 2,
                'title' => 'PPT Listening Practice',
                'file_path' => 'ppts/listening_practice.pptx',
                'status' => 'submitted',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'lesson_plan_id' => 3,
                'title' => 'PPT Reading Mandarin',
                'file_path' => 'ppts/reading_mandarin.pptx',
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'lesson_plan_id' => 3,
                'title' => 'PPT Grammar Mandarin',
                'file_path' => 'ppts/grammar_mandarin.pptx',
                'status' => 'revision',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}