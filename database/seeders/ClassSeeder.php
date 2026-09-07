<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSeeder extends Seeder
{
    /**
     * DATA DISESUAIKAN - class dibuat per LEVEL sesuai brosur Haoyou Educator
     */
    public function run(): void
    {
        $teacherIds = Teacher::pluck('id')->toArray();

        if (empty($teacherIds)) {
            $this->command?->warn('Belum ada Teacher, pastikan TeacherSeeder sudah dijalankan.');
            return;
        }

        // Paket Regular Anak (60 menit) - untuk Maochong & Jianer
        $regularAnakPackageIds = DB::table('program_packages')
            ->where('course_type', 'Regular')
            ->where('duration_minutes', 60)
            ->where('package_name', 'like', 'Regular Class Anak%')
            ->pluck('id')
            ->toArray();

        // Paket Regular Dewasa (90 menit) - untuk Hudie & Feixiang
        $regularDewasaPackageIds = DB::table('program_packages')
            ->where('course_type', 'Regular')
            ->where('duration_minutes', 90)
            ->where('package_name', 'like', 'Regular Class Dewasa%')
            ->pluck('id')
            ->toArray();

        if (empty($regularAnakPackageIds) || empty($regularDewasaPackageIds)) {
            $this->command?->warn('Paket Regular Anak/Dewasa tidak ditemukan, pastikan ProgramPackageSeeder sudah dijalankan.');
            return;
        }

        // Level per group sesuai brosur
        $levelsAB = ['1A', '1B', '2A', '2B', '3A', '3B', '4A', '4B', '5A', '5B', '6A', '6B'];
        $levelsAD = ['1A', '1B', '1C', '1D', '2A', '2B', '2C', '2D', '3A', '3B', '3C', '3D'];

        $groups = [
            'Maochong' => ['levels' => $levelsAB, 'packages' => $regularAnakPackageIds],   // 3-6 tahun, 60 menit
            'Jianer'   => ['levels' => $levelsAB, 'packages' => $regularAnakPackageIds],   // 7-9 tahun, 60 menit
            'Hudie'    => ['levels' => $levelsAD, 'packages' => $regularDewasaPackageIds], // 10-14 tahun, 90 menit
            'Feixiang' => ['levels' => $levelsAD, 'packages' => $regularDewasaPackageIds], // 15+ tahun, 90 menit
        ];

        foreach ($groups as $groupName => $data) {
            $packageIds = $data['packages'];

            foreach ($data['levels'] as $index => $level) {
                $packageId = $packageIds[$index % count($packageIds)];

                ClassModel::factory()->create([
                    'class_name'         => "{$groupName} {$level}",
                    'program_package_id' => $packageId,
                    'teacher_id'         => fake()->randomElement($teacherIds),
                ]);
            }
        }

        // HSK 1 - HSK 6, masing-masing 1 batch class, dicocokkan by nama paket HSK Class
        $hskClasses = [
            'HSK 1' => 'HSK 1 (60 Menit / 2 Bulan)',
            'HSK 2' => 'HSK 2 (60 Menit / 3 Bulan)',
            'HSK 3' => 'HSK 3 (90 Menit / 6 Bulan)',
            'HSK 4' => 'HSK 4 (90 Menit / 12 Bulan)',
            'HSK 5' => 'HSK 5 (90 Menit / 24 Bulan)',
            'HSK 6' => 'HSK 6 (90 Menit / 30 Bulan)',
        ];

        foreach ($hskClasses as $className => $packageName) {
            $this->createClassByPackageName($className, $packageName, $teacherIds);
        }
    }

    private function createClassByPackageName(string $className, string $packageName, array $teacherIds): void
    {
        $packageId = DB::table('program_packages')
            ->where('course_type', 'Regular')
            ->where('package_name', $packageName)
            ->value('id');

        if (! $packageId) {
            $this->command?->warn("Package '{$packageName}' tidak ditemukan, class '{$className}' dilewati.");
            return;
        }

        ClassModel::factory()->create([
            'class_name'         => $className,
            'program_package_id' => $packageId,
            'teacher_id'         => fake()->randomElement($teacherIds),
        ]);
    }
}