<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeachingJournalSeeder extends Seeder
{
    /**
     * Dibuat berbasis class_schedules: tiap jadwal kelas mendapat 1 jurnal
     * mengajar, memakai teacher pemegang kelas & materi dari package terkait.
     */
    public function run(): void
    {
        $now = now();

        $teacherIds = DB::table('teachers')->pluck('id')->toArray();

        $schedules = DB::table('class_schedules')
            ->join('classes', 'classes.id', '=', 'class_schedules.class_id')
            ->select('class_schedules.id as schedule_id', 'classes.id as class_id', 'classes.teacher_id', 'classes.program_package_id')
            ->get();

        foreach ($schedules as $schedule) {
            $materialId = DB::table('materials')
                ->where('program_package_id', $schedule->program_package_id)
                ->inRandomOrder()
                ->value('id');

            if (!$materialId || !$schedule->teacher_id) {
                continue;
            }

            $isSubstitute = fake()->boolean(15);
            $substituteTeacherId = null;

            if ($isSubstitute) {
                $candidates = array_diff($teacherIds, [$schedule->teacher_id]);
                $substituteTeacherId = $candidates ? fake()->randomElement($candidates) : null;
            }

            DB::table('teaching_journals')->insert([
                'teacher_id' => $schedule->teacher_id,
                'class_id' => $schedule->class_id,
                'class_schedule_id' => $schedule->schedule_id,
                'material_id' => $materialId,
                'is_substitute' => $isSubstitute,
                'substitute_teacher_id' => $substituteTeacherId,
                'class_status' => fake()->randomElement(['Conducted', 'Conducted', 'Conducted', 'Cancelled', 'Rescheduled']),
                'learning_activities' => fake()->paragraph(),
                'problems' => fake()->optional()->sentence(),
                'solutions' => fake()->optional()->sentence(),
                'results' => fake()->optional()->sentence(),
                'notes' => fake()->optional()->sentence(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}