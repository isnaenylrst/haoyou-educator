<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\Program;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $teacherIds = Teacher::pluck('id')->toArray();

        if (empty($teacherIds)) {
            $this->command?->warn('Belum ada Teacher, pastikan TeacherSeeder sudah dijalankan.');
            return;
        }

        $dailyActivity = Program::where('program_name', 'Daily Activity')->first();

        if (! $dailyActivity) {
            $this->command?->warn('Program Daily Activity tidak ditemukan.');
            return;
        }

        // course_type_id sudah tidak ada lagi — cukup filter program_id
        $regularAnakPackageIds = ProgramPackage::where('program_id', $dailyActivity->id)
            ->where('duration_minutes', 60)
            ->where('package_name', 'like', 'Regular Class Anak%')
            ->pluck('id')
            ->toArray();

        $regularDewasaPackageIds = ProgramPackage::where('program_id', $dailyActivity->id)
            ->where('duration_minutes', 90)
            ->where('package_name', 'like', 'Regular Class Dewasa%')
            ->pluck('id')
            ->toArray();

        if (empty($regularAnakPackageIds) || empty($regularDewasaPackageIds)) {
            $this->command?->warn('Paket Regular Anak/Dewasa tidak ditemukan, pastikan ProgramPackageSeeder sudah dijalankan.');
            return;
        }

        $levelsAB = ['1A', '1B', '2A', '2B', '3A', '3B', '4A', '4B', '5A', '5B', '6A', '6B'];
        $levelsAD = ['1A', '1B', '1C', '1D', '2A', '2B', '2C', '2D', '3A', '3B', '3C', '3D'];

        $groups = [
            'Maochong' => ['levels' => $levelsAB, 'packages' => $regularAnakPackageIds],
            'Jianer'   => ['levels' => $levelsAB, 'packages' => $regularAnakPackageIds],
            'Hudie'    => ['levels' => $levelsAD, 'packages' => $regularDewasaPackageIds],
            'Feixiang' => ['levels' => $levelsAD, 'packages' => $regularDewasaPackageIds],
        ];

        foreach ($groups as $groupName => $data) {
            $category = ProgramCategory::where('program_id', $dailyActivity->id)
                ->where('category_name', $groupName)
                ->first();

            if (! $category) {
                $this->command?->warn("Kategori '{$groupName}' tidak ditemukan, dilewati.");
                continue;
            }

            $packageIds = $data['packages'];

            foreach ($data['levels'] as $index => $levelName) {
                $level = ProgramLevel::where('category_id', $category->id)
                    ->where('level_name', $levelName)
                    ->first();

                if (! $level) {
                    $this->command?->warn("Level '{$levelName}' pada kategori '{$groupName}' tidak ditemukan, dilewati.");
                    continue;
                }

                $packageId = $packageIds[$index % count($packageIds)];

                ClassModel::factory()->create([
                    'class_name'         => "{$groupName} {$levelName}",
                    'program_package_id' => $packageId,
                    'level_id'           => $level->id,
                    'teacher_id'         => fake()->randomElement($teacherIds),
                ]);
            }
        }

        $this->seedHskClasses($teacherIds);
    }

    private function seedHskClasses(array $teacherIds): void
    {
        $hsk = Program::where('program_name', 'HSK')->first();

        if (! $hsk) {
            $this->command?->warn('Program HSK tidak ditemukan.');
            return;
        }

        $hskClassCategory = ProgramCategory::where('program_id', $hsk->id)
            ->where('category_name', 'HSK Class')
            ->first();

        if (! $hskClassCategory) {
            $this->command?->warn('Kategori HSK Class tidak ditemukan.');
            return;
        }

        foreach (range(1, 6) as $levelNumber) {
            $level = ProgramLevel::where('category_id', $hskClassCategory->id)
                ->where('level_name', "HSK {$levelNumber}")
                ->first();

            if (! $level) {
                $this->command?->warn("Level 'HSK {$levelNumber}' tidak ditemukan, dilewati.");
                continue;
            }

            // Dicari lewat level_id, bukan nama paket persis — lebih aman karena
            // package_name HSK Class sekarang menyertakan catatan durasi bulan
            $package = ProgramPackage::where('level_id', $level->id)->first();

            if (! $package) {
                $this->command?->warn("Package untuk 'HSK {$levelNumber}' tidak ditemukan, dilewati.");
                continue;
            }

            ClassModel::factory()->create([
                'class_name'         => "HSK {$levelNumber}",
                'program_package_id' => $package->id,
                'level_id'           => $level->id,
                'teacher_id'         => fake()->randomElement($teacherIds),
            ]);
        }
    }
}