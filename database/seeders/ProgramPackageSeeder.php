<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use Illuminate\Database\Seeder;

class ProgramPackageSeeder extends Seeder
{
    public function run(): void
    {
        $dailyActivity = Program::where('program_name', 'Daily Activity')->firstOrFail();
        $hsk = Program::where('program_name', 'HSK')->firstOrFail();

        // ── Daily Activity: harga tidak bergantung kategori/level (category_id & level_id null) ──
        $regularPackages = [
            ['Regular Class Anak - 1x/Minggu (1 Bulan / 4 Pertemuan)', 60, 4, 550000],
            ['Regular Class Anak - 2x/Minggu (1 Bulan / 8 Pertemuan)', 60, 8, 950000],
            ['Regular Class Anak - 1x/Minggu (3 Bulan / 12 Pertemuan)', 60, 12, 1500000],
            ['Regular Class Anak - 2x/Minggu (3 Bulan / 24 Pertemuan)', 60, 24, 2500000],
            ['Regular Class Dewasa - 1x/Minggu (1 Bulan / 4 Pertemuan)', 90, 4, 550000],
            ['Regular Class Dewasa - 2x/Minggu (1 Bulan / 8 Pertemuan)', 90, 8, 950000],
            ['Regular Class Dewasa - 1x/Minggu (3 Bulan / 12 Pertemuan)', 90, 12, 1500000],
            ['Regular Class Dewasa - 2x/Minggu (3 Bulan / 24 Pertemuan)', 90, 24, 2500000],
        ];
        foreach ($regularPackages as [$name, $duration, $meetings, $price]) {
            ProgramPackage::create([
                'program_id' => $dailyActivity->id,
                'category_id' => null,
                'level_id' => null,
                'package_name' => $name,
                'duration_minutes' => $duration,
                'total_meetings' => $meetings,
                'min_students' => 4,
                'max_students' => 8,
                'price' => $price,
            ]);
        }

        // ── HSK Class (kurikulum penuh) ──
        $hskClassCategory = ProgramCategory::where('program_id', $hsk->id)
            ->where('category_name', 'HSK Class')->firstOrFail();

        // [level, total_meetings dibeli, price, duration_minutes, catatan_bulan]
        $hskClassData = [
            [1, 16, 1500000, 60, '2 Bulan'],
            [2, 24, 2500000, 60, '3 Bulan'],
            [3, 24, 3500000, 90, '6 Bulan (dari total 48 pertemuan)'],
            [4, 24, 3750000, 90, '12 Bulan (dari total 96 pertemuan)'],
            [5, 24, 4500000, 90, '24 Bulan (dari total 192 pertemuan)'],
            [6, 24, 5000000, 90, '30 Bulan (dari total 240 pertemuan)'],
        ];
        foreach ($hskClassData as [$level, $meetings, $price, $duration, $note]) {
            $levelModel = ProgramLevel::where('category_id', $hskClassCategory->id)
                ->where('level_name', 'HSK ' . $level)->firstOrFail();

            ProgramPackage::create([
                'program_id' => $hsk->id,
                'category_id' => $hskClassCategory->id,
                'level_id' => $levelModel->id,
                'package_name' => "HSK Class - Level {$level} ({$note})",
                'duration_minutes' => $duration,
                'total_meetings' => $meetings,
                'min_students' => 2,
                'max_students' => $level <= 3 ? 6 : 4,
                'price' => $price,
            ]);
        }

        // ── HSK Preparation (kilat) ──
        $hskPrepCategory = ProgramCategory::where('program_id', $hsk->id)
            ->where('category_name', 'HSK Preparation')->firstOrFail();

        $hskPrepData = [
            [1, 8, 750000, 60, '1 Bulan'],
            [2, 8, 850000, 60, '1 Bulan'],
            [3, 8, 950000, 60, '1 Bulan'],
            [4, 12, 1500000, 90, '1 Bulan'],
            [5, 24, 3000000, 90, '2 Bulan'],
            [6, 36, 4500000, 90, '3 Bulan'],
        ];
        foreach ($hskPrepData as [$level, $meetings, $price, $duration, $note]) {
            $levelModel = ProgramLevel::where('category_id', $hskPrepCategory->id)
                ->where('level_name', 'HSK ' . $level)->firstOrFail();

            ProgramPackage::create([
                'program_id' => $hsk->id,
                'category_id' => $hskPrepCategory->id,
                'level_id' => $levelModel->id,
                'package_name' => "HSK Preparation - Level {$level} ({$note})",
                'duration_minutes' => $duration,
                'total_meetings' => $meetings,
                'min_students' => 2,
                'max_students' => $level <= 3 ? 6 : 4,
                'price' => $price,
            ]);
        }
    }
}