<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Material;
use App\Models\Teacher;
use App\Models\TeacherMaterial;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherMaterialSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap materi mendapat 1 PPT dari guru, direview user Curriculum.
     */
    public function run(): void
    {
        $teacherIds = Teacher::pluck('id')->toArray();

        if (empty($teacherIds)) {
            $this->command?->warn('Belum ada Teacher, TeacherMaterialSeeder dilewati.');
            return;
        }

        $curriculumLevelId = Level::where('nama_level', 'Curriculum')->value('id_level');
        $reviewerIds = User::where('level_id', $curriculumLevelId)->pluck('id')->toArray();

        if (empty($reviewerIds)) {
            $this->command?->warn('Belum ada User dengan level "Curriculum", TeacherMaterialSeeder dilewati.');
            return;
        }

        $materialIds = Material::pluck('id');

        foreach ($materialIds as $materialId) {
            $teacherMaterial = TeacherMaterial::factory()->create([
                'material_id' => $materialId,
                'teacher_id' => fake()->randomElement($teacherIds),
            ]);

            if ($teacherMaterial->status !== 'Pending') {
                $teacherMaterial->update(['approved_by' => fake()->randomElement($reviewerIds)]);
            }
        }
    }
}