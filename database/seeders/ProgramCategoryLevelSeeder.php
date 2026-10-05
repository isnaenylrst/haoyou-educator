<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use Illuminate\Database\Seeder;

class ProgramCategoryLevelSeeder extends Seeder
{
    public function run(): void
    {
        $dailyActivity = Program::where('program_name', 'Daily Activity')->firstOrFail();
        $hsk = Program::where('program_name', 'HSK')->firstOrFail();

        // ── Daily Activity: Anak (Maochong, Jianer) ──
        $anakLevels = ['1A','1B','2A','2B','3A','3B','4A','4B','5A','5B','6A','6B'];
        foreach (['Maochong' => [3, 6], 'Jianer' => [7, 9]] as $name => [$min, $max]) {
            $category = ProgramCategory::create([
                'program_id' => $dailyActivity->id,
                'category_name' => $name,
                'min_age' => $min,
                'max_age' => $max,
            ]);
            foreach ($anakLevels as $i => $levelName) {
                ProgramLevel::create([
                    'program_id' => $dailyActivity->id,
                    'category_id' => $category->id,
                    'level_name' => $levelName,
                    'sort_order' => $i + 1,
                ]);
            }
        }

        // ── Daily Activity: Dewasa (Hudie, Feixiang) ──
        $dewasaLevels = ['1A','1B','1C','1D','2A','2B','2C','2D','3A','3B','3C','3D'];
        foreach (['Hudie' => [10, 14], 'Feixiang' => [15, null]] as $name => [$min, $max]) {
            $category = ProgramCategory::create([
                'program_id' => $dailyActivity->id,
                'category_name' => $name,
                'min_age' => $min,
                'max_age' => $max,
            ]);
            foreach ($dewasaLevels as $i => $levelName) {
                ProgramLevel::create([
                    'program_id' => $dailyActivity->id,
                    'category_id' => $category->id,
                    'level_name' => $levelName,
                    'sort_order' => $i + 1,
                ]);
            }
        }

        // ── HSK: dua kategori/jalur — Class (kurikulum penuh) & Preparation (kilat) ──
        $hskClass = ProgramCategory::create([
            'program_id' => $hsk->id,
            'category_name' => 'HSK Class',
        ]);
        $hskPrep = ProgramCategory::create([
            'program_id' => $hsk->id,
            'category_name' => 'HSK Preparation',
        ]);

        foreach ([$hskClass, $hskPrep] as $category) {
            foreach (range(1, 6) as $i => $level) {
                ProgramLevel::create([
                    'program_id' => $hsk->id,
                    'category_id' => $category->id,
                    'level_name' => 'HSK ' . $level,
                    'sort_order' => $level,
                ]);
            }
        }
    }
}