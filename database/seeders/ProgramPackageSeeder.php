<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramPackageSeeder extends Seeder
{
    /**
     * Data harga & paket berdasarkan brosur Haoyou Educator.
     * Angka kedua pada "Pertemuan: 24/48" dst (HSK 3-6) tidak konsisten sebagai
     * kelipatan, sehingga total_meetings memakai angka PERTAMA (24), konsisten
     * dengan HSK 1 & 2.
     */
    public function run(): void
    {
        $now = now();

        $dailyActivityId = DB::table('programs')->where('program_name', 'Daily Activity')->value('id');
        $hskId = DB::table('programs')->where('program_name', 'HSK')->value('id');

        // Regular Class (group 4-8 orang, 90 menit)
        $regularPackages = [
            ['name' => 'Regular Class - 1x/Minggu (1 Bulan / 4 Pertemuan)', 'meetings' => 4, 'price' => 550000],
            ['name' => 'Regular Class - 2x/Minggu (1 Bulan / 8 Pertemuan)', 'meetings' => 8, 'price' => 950000],
            ['name' => 'Regular Class - 1x/Minggu (3 Bulan / 12 Pertemuan)', 'meetings' => 12, 'price' => 1500000],
            ['name' => 'Regular Class - 2x/Minggu (3 Bulan / 24 Pertemuan)', 'meetings' => 24, 'price' => 2500000],
        ];
        foreach ($regularPackages as $pkg) {
            $this->insertPackage($dailyActivityId, 'Regular', $pkg['name'], 90, $pkg['meetings'], 4, 8, $pkg['price'], $now);
        }

        // Private VIP (1 orang, guru lokal, 90 menit)
        $this->insertPackage($dailyActivityId, 'Private', 'Private VIP (1 Orang) - 4 Pertemuan (1 Bulan)', 90, 4, 1, 1, 1200000, $now);
        $this->insertPackage($dailyActivityId, 'Private', 'Private VIP (1 Orang) - 8 Pertemuan (1 Bulan)', 90, 8, 1, 1, 2000000, $now);

        // Private Exclusive (2-4 orang, guru lokal, 90 menit)
        $this->insertPackage($dailyActivityId, 'Private', 'Private Exclusive (2-4 Orang) - 4 Pertemuan (1 Bulan)', 90, 4, 2, 4, 800000, $now);
        $this->insertPackage($dailyActivityId, 'Private', 'Private Exclusive (2-4 Orang) - 8 Pertemuan (1 Bulan)', 90, 8, 2, 4, 1400000, $now);

        // Private Native VIP (1 on 1, guru asli China, 60 menit)
        $this->insertPackage($dailyActivityId, 'Private', 'Private Native VIP (1 on 1) - 4 Pertemuan (60 Menit)', 60, 4, 1, 1, 1600000, $now);
        $this->insertPackage($dailyActivityId, 'Private', 'Private Native VIP (1 on 1) - 8 Pertemuan (60 Menit)', 60, 8, 1, 1, 2800000, $now);

        // Private Native Exclusive (2-4 orang, guru asli China, 60 menit)
        $this->insertPackage($dailyActivityId, 'Private', 'Private Native Exclusive (2-4 Orang) - 4 Pertemuan (60 Menit)', 60, 4, 2, 4, 1200000, $now);
        $this->insertPackage($dailyActivityId, 'Private', 'Private Native Exclusive (2-4 Orang) - 8 Pertemuan (60 Menit)', 60, 8, 2, 4, 2000000, $now);

        // HSK 1-6
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