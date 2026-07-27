<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\ProgramPackage;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * DUMMY DATA - 6 kelas contoh, tiap kelas dikaitkan ke package & teacher acak.
     */
    public function run(): void
    {
        $packageIds = ProgramPackage::pluck('id')->toArray();
        $teacherIds = Teacher::pluck('id')->toArray();

        $classNames = ['Maochong A', 'Jianer A', 'Hudie A', 'Feixiang A', 'HSK 1 Batch 1', 'HSK 2 Batch 1'];

        foreach ($classNames as $className) {
            ClassModel::factory()->create([
                'class_name' => $className,
                'program_package_id' => fake()->randomElement($packageIds),
                'teacher_id' => fake()->randomElement($teacherIds),
            ]);
        }
    }
}
