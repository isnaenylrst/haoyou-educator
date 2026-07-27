<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use Illuminate\Database\Seeder;

class ClassScheduleSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap kelas punya 1-2 jadwal per minggu.
     */
    public function run(): void
    {
        $classIds = ClassModel::pluck('id');

        foreach ($classIds as $classId) {
            ClassSchedule::factory()
                ->count(fake()->numberBetween(1, 2))
                ->create(['class_id' => $classId]);
        }
    }
}
