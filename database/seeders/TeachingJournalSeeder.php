<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\Material;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use Illuminate\Database\Seeder;

class TeachingJournalSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap jadwal kelas mendapat 1 jurnal mengajar, memakai
     * teacher pemegang kelas & materi dari level terkait.
     */
    public function run(): void
    {
        $teacherIds = Teacher::pluck('id')->toArray();

        $schedules = ClassSchedule::join('classes', 'classes.id', '=', 'class_schedules.class_id')
            ->select('class_schedules.id as schedule_id', 'classes.id as class_id', 'classes.teacher_id', 'classes.level_id')
            ->get();

        foreach ($schedules as $schedule) {
            $materialId = Material::where('level_id', $schedule->level_id)
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

            $factory = $substituteTeacherId
                ? TeachingJournal::factory()->substitute($substituteTeacherId)
                : TeachingJournal::factory();

            $factory->create([
                'teacher_id' => $schedule->teacher_id,
                'class_id' => $schedule->class_id,
                'class_schedule_id' => $schedule->schedule_id,
                'session_date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
                'material_id' => $materialId,
            ]);
        }
    }
}