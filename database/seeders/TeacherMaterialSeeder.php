<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherMaterialSeeder extends Seeder
{
    /**
     * Tiap materi mendapat 1 PPT yang diunggah oleh guru dan direview
     * oleh user Curriculum (approved_by).
     */
    public function run(): void
    {
        $now = now();

        $teacherIds = DB::table('teachers')->pluck('id')->toArray();

        $curriculumLevelId = DB::table('levels')->where('nama_level', 'Curriculum')->value('id_level');
        $reviewerIds = DB::table('users')->where('level_id', $curriculumLevelId)->pluck('id')->toArray();

        $materials = DB::table('materials')->select('id', 'title')->get();

        foreach ($materials as $material) {
            $status = fake()->randomElement(['Pending', 'Approved', 'Approved', 'Rejected']);
            $isReviewed = $status !== 'Pending';

            DB::table('teacher_materials')->insert([
                'material_id' => $material->id,
                'teacher_id' => fake()->randomElement($teacherIds),
                'ppt_title' => 'PPT - ' . $material->title,
                'ppt_file_path' => 'ppt/material_' . $material->id . '.pptx',
                'status' => $status,
                'review_note' => $isReviewed ? fake()->sentence() : null,
                'approved_by' => $isReviewed ? fake()->randomElement($reviewerIds) : null,
                'approved_at' => $isReviewed ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}