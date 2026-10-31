<?php

namespace Database\Seeders;

use App\Models\LetterType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KurikulumModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Letter Types
        $letterTypes = [
            ['code' => 'SURAT_LIBUR', 'name' => 'Surat Libur', 'description' => 'Pemberitahuan libur kegiatan belajar mengajar.'],
            ['code' => 'SURAT_DINAS', 'name' => 'Surat Dinas', 'description' => 'Surat tugas atau dinas pengajar.'],
            ['code' => 'LOA', 'name' => 'Letter of Acceptance (LOA)', 'description' => 'Surat penerimaan atau penugasan resmi.'],
            ['code' => 'SP1', 'name' => 'Surat Peringatan 1 (SP1)', 'description' => 'Surat Peringatan Pertama untuk tenaga pengajar.'],
            ['code' => 'SP2', 'name' => 'Surat Peringatan 2 (SP2)', 'description' => 'Surat Peringatan Kedua untuk tenaga pengajar.'],
            ['code' => 'SP3', 'name' => 'Surat Peringatan 3 (SP3)', 'description' => 'Surat Peringatan Ketiga untuk tenaga pengajar.'],
        ];

        foreach ($letterTypes as $lt) {
            DB::table('letter_types')->updateOrInsert(
                ['code' => $lt['code']],
                [
                    'name' => $lt['name'],
                    'description' => $lt['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Ensure Achievement Levels include Sufficient, Proficient, Excellent (Point 6)
        $levels = [
            [
                'level' => 'Excellent',
                'description' => 'Demonstrates outstanding mastery of the material and applies it accurately and confidently without assistance.',
                'sort_order' => 1,
            ],
            [
                'level' => 'Proficient',
                'description' => 'Demonstrates a solid understanding of the material and applies it correctly with minimal assistance.',
                'sort_order' => 2,
            ],
            [
                'level' => 'Sufficient',
                'description' => 'Demonstrates an adequate basic understanding of the material and meets the minimum required competency standards.',
                'sort_order' => 3,
            ],
        ];

        foreach ($levels as $l) {
            DB::table('achievement_levels')->updateOrInsert(
                ['level' => $l['level']],
                [
                    'description' => $l['description'],
                    'sort_order' => $l['sort_order'],
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Ensure sample Program Categories exist if empty (HSK, Jianer, Maochong, etc.)
        $dailyActivity = DB::table('programs')->where('program_name', 'Daily Activity')->first();
        if ($dailyActivity) {
            foreach (['Maochong', 'Jianer', 'Hudie', 'Feixiang'] as $idx => $catName) {
                DB::table('program_categories')->updateOrInsert(
                    ['program_id' => $dailyActivity->id, 'category_name' => $catName],
                    ['sort_order' => $idx + 1, 'updated_at' => now()]
                );
            }
        }

        $hsk = DB::table('programs')->where('program_name', 'HSK')->first();
        if ($hsk) {
            foreach (['HSK Class', 'HSK Preparation'] as $idx => $catName) {
                DB::table('program_categories')->updateOrInsert(
                    ['program_id' => $hsk->id, 'category_name' => $catName],
                    ['sort_order' => $idx + 1, 'updated_at' => now()]
                );
            }
        }
    }
}

