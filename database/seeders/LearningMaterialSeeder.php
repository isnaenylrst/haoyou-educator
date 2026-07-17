<?php

namespace Database\Seeders;

use App\Models\LearningMaterial;
use Illuminate\Database\Seeder;

class LearningMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LearningMaterial::insert([

            [
                'program_id' => 1,
                'curriculum_id' => 1,
                'title' => 'Pengenalan Hanzi',
                'level' => '1A',
                'meeting' => 1,
                'description' => 'Materi pengenalan huruf Hanzi.',
                'file_path' => 'learning_materials/hanzi_1A.pdf',
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'program_id' => 1,
                'curriculum_id' => 1,
                'title' => 'Percakapan Dasar',
                'level' => '1A',
                'meeting' => 2,
                'description' => 'Percakapan sederhana dalam Bahasa Mandarin.',
                'file_path' => 'learning_materials/conversation_1A.pdf',
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'program_id' => 2,
                'curriculum_id' => 1,
                'title' => 'Reading Practice',
                'level' => '2A',
                'meeting' => 5,
                'description' => 'Latihan membaca teks pendek.',
                'file_path' => 'learning_materials/reading_2A.pdf',
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'program_id' => 2,
                'curriculum_id' => 1,
                'title' => 'Listening Exercise',
                'level' => '2B',
                'meeting' => 8,
                'description' => 'Latihan mendengarkan audio.',
                'file_path' => 'learning_materials/listening_2B.pdf',
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'program_id' => 3,
                'curriculum_id' => 1,
                'title' => 'Chinese Grammar',
                'level' => '3A',
                'meeting' => 10,
                'description' => 'Pembelajaran tata bahasa Mandarin.',
                'file_path' => 'learning_materials/grammar_3A.pdf',
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}