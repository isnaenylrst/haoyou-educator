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
        // Harga = total harga paket (bukan per bulan). Contoh: 3 bulan / 12 pertemuan = Rp 1.500.000
        // [nama, duration_minutes, duration_months, total_meetings, price]
        $regularPackages = [
            ['Regular Class Anak - 1x/Minggu (1 Bulan / 4 Pertemuan)', 60, 1, 4, 550000],
            ['Regular Class Anak - 2x/Minggu (1 Bulan / 8 Pertemuan)', 60, 1, 8, 950000],
            ['Regular Class Anak - 1x/Minggu (3 Bulan / 12 Pertemuan)', 60, 3, 12, 1500000],
            ['Regular Class Anak - 2x/Minggu (3 Bulan / 24 Pertemuan)', 60, 3, 24, 2500000],
            ['Regular Class Dewasa - 1x/Minggu (1 Bulan / 4 Pertemuan)', 90, 1, 4, 550000],
            ['Regular Class Dewasa - 2x/Minggu (1 Bulan / 8 Pertemuan)', 90, 1, 8, 950000],
            ['Regular Class Dewasa - 1x/Minggu (3 Bulan / 12 Pertemuan)', 90, 3, 12, 1500000],
            ['Regular Class Dewasa - 2x/Minggu (3 Bulan / 24 Pertemuan)', 90, 3, 24, 2500000],
        ];
        foreach ($regularPackages as [$name, $minutes, $months, $meetings, $price]) {
            ProgramPackage::updateOrCreate(
                ['program_id' => $dailyActivity->id, 'package_name' => $name],
                [
                    'category_id' => null,
                    'level_id' => null,
                    'duration_minutes' => $minutes,
                    'duration_months' => $months,
                    'total_meetings' => $meetings,
                    'min_students' => 4,
                    'max_students' => 8,
                    'price' => $price,
                ]
            );
        }

        // ── HSK Class (kurikulum penuh) ──
        $hskClassCategory = ProgramCategory::where('program_id', $hsk->id)
            ->where('category_name', 'HSK Class')->firstOrFail();

        // [level, total_meetings dibeli, price, duration_minutes, duration_months, catatan]
        // duration_months = lama satu level HSK sesuai brosur (mis. HSK 3 = 6 bulan, total 48 pertemuan)
        $hskClassData = [
            [1, 16, 1500000, 60, 2, '2 Bulan'],
            [2, 24, 2500000, 60, 3, '3 Bulan'],
            [3, 24, 3500000, 90, 6, '6 Bulan (dari total 48 pertemuan)'],
            [4, 24, 3750000, 90, 12, '12 Bulan (dari total 96 pertemuan)'],
            [5, 24, 4500000, 90, 24, '24 Bulan (dari total 192 pertemuan)'],
            [6, 24, 5000000, 90, 30, '30 Bulan (dari total 240 pertemuan)'],
        ];
        foreach ($hskClassData as [$level, $meetings, $price, $minutes, $months, $note]) {
            $levelModel = ProgramLevel::where('category_id', $hskClassCategory->id)
                ->where('level_name', 'HSK ' . $level)->firstOrFail();

            ProgramPackage::updateOrCreate(
                ['program_id' => $hsk->id, 'package_name' => "HSK Class - Level {$level} ({$note})"],
                [
                    'category_id' => $hskClassCategory->id,
                    'level_id' => $levelModel->id,
                    'duration_minutes' => $minutes,
                    'duration_months' => $months,
                    'total_meetings' => $meetings,
                    'min_students' => 2,
                    'max_students' => $level <= 3 ? 6 : 4,
                    'price' => $price,
                ]
            );
        }

        // ── HSK Preparation (kilat) ──
        $hskPrepCategory = ProgramCategory::where('program_id', $hsk->id)
            ->where('category_name', 'HSK Preparation')->firstOrFail();

        // [level, total_meetings, price, duration_minutes, duration_months]
        $hskPrepData = [
            [1, 8, 750000, 60, 1],
            [2, 8, 850000, 60, 1],
            [3, 8, 950000, 60, 1],
            [4, 12, 1500000, 90, 1],
            [5, 24, 3000000, 90, 2],
            [6, 36, 4500000, 90, 3],
        ];
        foreach ($hskPrepData as [$level, $meetings, $price, $minutes, $months]) {
            $levelModel = ProgramLevel::where('category_id', $hskPrepCategory->id)
                ->where('level_name', 'HSK ' . $level)->firstOrFail();

            ProgramPackage::updateOrCreate(
                ['program_id' => $hsk->id, 'package_name' => "HSK Preparation - Level {$level} ({$months} Bulan)"],
                [
                    'category_id' => $hskPrepCategory->id,
                    'level_id' => $levelModel->id,
                    'duration_minutes' => $minutes,
                    'duration_months' => $months,
                    'total_meetings' => $meetings,
                    'min_students' => 2,
                    'max_students' => $level <= 3 ? 6 : 4,
                    'price' => $price,
                ]
            );
        }
    }
}