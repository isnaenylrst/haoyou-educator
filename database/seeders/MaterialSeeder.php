<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Material;
use App\Models\ProgramPackage;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap program_package mendapat 3 materi (meeting 1-3),
     * diunggah oleh user Curriculum.
     */
    public function run(): void
    {
        $curriculumLevelId = Level::where('nama_level', 'Curriculum')->value('id_level');
        $uploaderIds = User::where('level_id', $curriculumLevelId)->pluck('id')->toArray();

        $packageIds = ProgramPackage::pluck('id');

        foreach ($packageIds as $packageId) {
            for ($meeting = 1; $meeting <= 3; $meeting++) {
                Material::factory()->create([
                    'program_package_id' => $packageId,
                    'uploaded_by' => fake()->randomElement($uploaderIds),
                    'meeting_number' => $meeting,
                ]);
            }
        }
    }
}
