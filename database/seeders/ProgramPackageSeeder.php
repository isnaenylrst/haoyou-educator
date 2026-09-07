<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramPackageSeeder extends Seeder
{
    /**
     * DATA PASTI - harga & paket sesuai brosur Haoyou Educator.
     */
    public function run(): void
    {
        $now = now();

        $dailyActivityId = DB::table('programs')->where('program_name', 'Daily Activity')->value('id');
        $hskId = DB::table('programs')->where('program_name', 'HSK')->value('id');

        // ==== REGULAR CLASS - ANAK (60 Menit / 2-9 tahun) ====
        // -> dipakai Maochong (3-6th) & Jianer (7-9th)
        $regularAnakPackages = [
            ['name' => 'Regular Class Anak - 1x/Minggu (1 Bulan / 4 Pertemuan)', 'meetings' => 4, 'price' => 550000],
            ['name' => 'Regular Class Anak - 2x/Minggu (1 Bulan / 8 Pertemuan)', 'meetings' => 8, 'price' => 950000],
            ['name' => 'Regular Class Anak - 1x/Minggu (3 Bulan / 12 Pertemuan)', 'meetings' => 12, 'price' => 1500000],
            ['name' => 'Regular Class Anak - 2x/Minggu (3 Bulan / 24 Pertemuan)', 'meetings' => 24, 'price' => 2500000],
        ];
        foreach ($regularAnakPackages as $pkg) {
            $this->insertPackage($dailyActivityId, 'Regular', $pkg['name'], 60, $pkg['meetings'], 4, 8, $pkg['price'], $now);
        }

        // ==== REGULAR CLASS - DEWASA (90 Menit / 9+ tahun) ====
        // -> dipakai Hudie (10-14th) & Feixiang (15+th)
        $regularDewasaPackages = [
            ['name' => 'Regular Class Dewasa - 1x/Minggu (1 Bulan / 4 Pertemuan)', 'meetings' => 4, 'price' => 550000],
            ['name' => 'Regular Class Dewasa - 2x/Minggu (1 Bulan / 8 Pertemuan)', 'meetings' => 8, 'price' => 950000],
            ['name' => 'Regular Class Dewasa - 1x/Minggu (3 Bulan / 12 Pertemuan)', 'meetings' => 12, 'price' => 1500000],
            ['name' => 'Regular Class Dewasa - 2x/Minggu (3 Bulan / 24 Pertemuan)', 'meetings' => 24, 'price' => 2500000],
        ];
        foreach ($regularDewasaPackages as $pkg) {
            $this->insertPackage($dailyActivityId, 'Regular', $pkg['name'], 90, $pkg['meetings'], 4, 8, $pkg['price'], $now);
        }

        // ==== HSK CLASS 1-6 ====
        $hskPackages = [
            ['name' => 'HSK 1 (60 Menit / 2 Bulan)', 'minutes' => 60, 'meetings' => 16, 'min' => 2, 'max' => 6, 'price' => 1500000],
            ['name' => 'HSK 2 (60 Menit / 3 Bulan)', 'minutes' => 60, 'meetings' => 24, 'min' => 2, 'max' => 6, 'price' => 2500000],
            ['name' => 'HSK 3 (90 Menit / 6 Bulan)', 'minutes' => 90, 'meetings' => 24, 'min' => 2, 'max' => 6, 'price' => 3500000],
            ['name' => 'HSK 4 (90 Menit / 12 Bulan)', 'minutes' => 90, 'meetings' => 24, 'min' => 2, 'max' => 4, 'price' => 3750000],
            ['name' => 'HSK 5 (90 Menit / 24 Bulan)', 'minutes' => 90, 'meetings' => 24, 'min' => 2, 'max' => 4, 'price' => 4500000],
            ['name' => 'HSK 6 (90 Menit / 30 Bulan)', 'minutes' => 90, 'meetings' => 24, 'min' => 2, 'max' => 4, 'price' => 5000000],
        ];
        foreach ($hskPackages as $pkg) {
            $this->insertPackage($hskId, 'Regular', $pkg['name'], $pkg['minutes'], $pkg['meetings'], $pkg['min'], $pkg['max'], $pkg['price'], $now);
        }

        // ==== HSK PREPARATION (paket persiapan ujian HSK, terpisah dari HSK Class) ====
        // $hskPrepPackages = [
        //     ['name' => 'HSK 1 Preparation (60 Menit / 1 Bulan)', 'minutes' => 60, 'meetings' => 8, 'price' => 750000],
        //     ['name' => 'HSK 2 Preparation (60 Menit / 1 Bulan)', 'minutes' => 60, 'meetings' => 8, 'price' => 850000],
        //     ['name' => 'HSK 3 Preparation (60 Menit / 1 Bulan)', 'minutes' => 60, 'meetings' => 8, 'price' => 950000],
        //     ['name' => 'HSK 4 Preparation (90 Menit / 1 Bulan)', 'minutes' => 90, 'meetings' => 12, 'price' => 1500000],
        //     ['name' => 'HSK 5 Preparation (90 Menit / 2 Bulan)', 'minutes' => 90, 'meetings' => 24, 'price' => 3000000],
        //     ['name' => 'HSK 6 Preparation (90 Menit / 3 Bulan)', 'minutes' => 90, 'meetings' => 36, 'price' => 4500000],
        // ];
        // foreach ($hskPrepPackages as $pkg) {
        //     $this->insertPackage($hskId, 'Regular', $pkg['name'], $pkg['minutes'], $pkg['meetings'], 2, 6, $pkg['price'], $now);
        // }

                // ==== PRIVATE (VIP & Exclusive) — dipakai untuk HSK & Daily Activity ====
        // Nama di-prefix nama program karena package_name harus unique.
        $privatePackages = [
            ['name' => 'Private VIP (1 Orang) - 4 Pertemuan (1 Bulan)', 'duration' => 90, 'meetings' => 4, 'min' => 1, 'max' => 1, 'price' => 1200000],
            ['name' => 'Private VIP (1 Orang) - 8 Pertemuan (1 Bulan)', 'duration' => 90, 'meetings' => 8, 'min' => 1, 'max' => 1, 'price' => 2000000],
            ['name' => 'Private Exclusive (2-4 Orang) - 4 Pertemuan (1 Bulan)', 'duration' => 90, 'meetings' => 4, 'min' => 2, 'max' => 4, 'price' => 800000],
            ['name' => 'Private Exclusive (2-4 Orang) - 8 Pertemuan (1 Bulan)', 'duration' => 90, 'meetings' => 8, 'min' => 2, 'max' => 4, 'price' => 1400000],
        ];

        foreach (['Daily Activity' => $dailyActivityId, 'HSK' => $hskId] as $programName => $programId) {
            foreach ($privatePackages as $pkg) {
                $this->insertPackage(
                    $programId,
                    'Private',
                    "{$programName} - {$pkg['name']}",
                    $pkg['duration'],
                    $pkg['meetings'],
                    $pkg['min'],
                    $pkg['max'],
                    $pkg['price'],
                    $now
                );
            }
        }

        // ==== PRIVATE NATIVE VIP 1 on 1 (60 menit) ====
        // $this->insertPackage($dailyActivityId, 'Private', 'Private Native VIP (1 on 1) - 4 Pertemuan (60 Menit)', 60, 4, 1, 1, 1600000, $now);
        // $this->insertPackage($dailyActivityId, 'Private', 'Private Native VIP (1 on 1) - 8 Pertemuan (60 Menit)', 60, 8, 1, 1, 2800000, $now);

        // ==== PRIVATE NATIVE EXCLUSIVE 2-4 org (60 menit) ====
        // $this->insertPackage($dailyActivityId, 'Private', 'Private Native Exclusive (2-4 Orang) - 4 Pertemuan (60 Menit)', 60, 4, 2, 4, 1200000, $now);
        // $this->insertPackage($dailyActivityId, 'Private', 'Private Native Exclusive (2-4 Orang) - 8 Pertemuan (60 Menit)', 60, 8, 2, 4, 2000000, $now);
    }

    private function insertPackage($programId, $courseType, $name, $duration, $meetings, $min, $max, $price, $now): void
    {
        DB::table('program_packages')->insert([
            'program_id' => $programId,
            'course_type' => $courseType,
            'package_name' => $name,
            'duration_minutes' => $duration,
            'total_meetings' => $meetings,
            'min_students' => $min,
            'max_students' => $max,
            'price' => $price,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}