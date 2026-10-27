<?php

namespace Database\Seeders;

use App\Models\PrivatePackage;
use Illuminate\Database\Seeder;

class PrivatePackageSeeder extends Seeder
{
    public function run(): void
    {
        // [nama, durasi_menit, duration_months, total_meetings, min_students, max_students, price]
        $packages = [
            ['Private VIP Class - 4 Pertemuan', 90, 1, 4, 1, 1, 1200000],
            ['Private VIP Class - 8 Pertemuan', 90, 1, 8, 1, 1, 2000000],
            ['Private Exclusive Class - 4 Pertemuan', 90, 1, 4, 2, 4, 800000],
            ['Private Exclusive Class - 8 Pertemuan', 90, 1, 8, 2, 4, 1400000],
            // ['Private VIP Native - 4 Pertemuan', 60, 1, 4, 1, 1, 1600000],
            // ['Private VIP Native - 8 Pertemuan', 60, 1, 8, 1, 1, 2800000],
            // ['Private Exclusive Native - 4 Pertemuan', 60, 1, 4, 2, 4, 1200000],
            // ['Private Exclusive Native - 8 Pertemuan', 60, 1, 8, 2, 4, 2000000],
        ];

        foreach ($packages as [$name, $minutes, $months, $meetings, $min, $max, $price]) {
            PrivatePackage::updateOrCreate(
                ['package_name' => $name],
                [
                    'duration_minutes' => $minutes,
                    'duration_months' => $months,
                    'total_meetings' => $meetings,
                    'min_students' => $min,
                    'max_students' => $max,
                    'price' => $price,
                ]
            );
        }
    }
}