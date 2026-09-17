<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Material;
use App\Models\ProgramLevel;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap program_level mendapat 3 materi (meeting 1-3),
     * diunggah oleh user Curriculum.
     */
    public function run(): void
    {
        $curriculumLevelId = Level::where('nama_level', 'Curriculum')->value('id_level');
        $uploaderIds = User::where('level_id', $curriculumLevelId)->pluck('id')->toArray();

        if (empty($uploaderIds)) {
            $this->command?->warn('Belum ada User dengan level "Curriculum", pastikan UserSeeder sudah dijalankan.');
            return;
        }

        $levelIds = ProgramLevel::pluck('id');

        foreach ($levelIds as $levelId) {
            for ($meeting = 1; $meeting <= 3; $meeting++) {
                Material::factory()->create([
                    'level_id' => $levelId,
                    'uploaded_by' => fake()->randomElement($uploaderIds),
                    'meeting_number' => $meeting,
                ]);
            }
        }
    }
}