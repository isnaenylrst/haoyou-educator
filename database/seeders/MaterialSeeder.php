<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Material;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Dummy data
     * Setiap kelas mempunyai materi Meeting 1-3
     */
    public function run(): void
    {
        $curriculumLevelId = Level::where('nama_level', 'Curriculum')
            ->value('id_level');

        $uploaderIds = User::where('level_id', $curriculumLevelId)
            ->pluck('id')
            ->toArray();

        $classIds = ClassModel::pluck('id');

        foreach ($classIds as $classId) {

            for ($meeting = 1; $meeting <= 3; $meeting++) {

                Material::factory()->create([

                    'class_id' => $classId,

                    'uploaded_by' => fake()->randomElement($uploaderIds),

                    'meeting_number' => $meeting,

                ]);

            }
        }
    }
}