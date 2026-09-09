<?php

namespace Database\Seeders;

use App\Models\PrivatePackage;
use Illuminate\Database\Seeder;

class PrivatePackageSeeder extends Seeder
{
    public function run(): void
    {
        // [nama, durasi_menit, total_meetings, min_students, max_students, price]
        $packages = [
            ['Private VIP Lokal - 4 Pertemuan', 90, 4, 1, 1, 1200000],
            ['Private VIP Lokal - 8 Pertemuan', 90, 8, 1, 1, 2000000],
            ['Private Exclusive Lokal - 4 Pertemuan', 90, 4, 2, 4, 800000],
            ['Private Exclusive Lokal - 8 Pertemuan', 90, 8, 2, 4, 1400000],
            ['Private VIP Native - 4 Pertemuan', 60, 4, 1, 1, 1600000],
            ['Private VIP Native - 8 Pertemuan', 60, 8, 1, 1, 2800000],
            ['Private Exclusive Native - 4 Pertemuan', 60, 4, 2, 4, 1200000],
            ['Private Exclusive Native - 8 Pertemuan', 60, 8, 2, 4, 2000000],
        ];

        foreach ($packages as [$name, $duration, $meetings, $min, $max, $price]) {
            PrivatePackage::create([
                'package_name' => $name,
                'duration_minutes' => $duration,
                'total_meetings' => $meetings,
                'min_students' => $min,
                'max_students' => $max,
                'price' => $price,
            ]);
        }
    }
}