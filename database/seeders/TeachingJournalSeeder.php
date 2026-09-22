<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\Material;
use App\Models\TeacherLeave;
use App\Models\TeachingJournal;
use Illuminate\Database\Seeder;

class TeachingJournalSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = ClassSchedule::join('classes', 'classes.id', '=', 'class_schedules.class_id')
            ->select('class_schedules.id as schedule_id', 'classes.id as class_id', 'classes.teacher_id', 'classes.level_id')
            ->get();

        foreach ($schedules as $schedule) {
            if (!$schedule->teacher_id) {
                continue;
            }

            $materialId = $schedule->level_id
                ? Material::where('level_id', $schedule->level_id)->inRandomOrder()->value('id')
                : null;

            if ($schedule->level_id && !$materialId) {
                continue;
            }

            $approvedLeave = TeacherLeave::where('class_schedule_id', $schedule->schedule_id)
                ->where('status', 'Approved')
                ->first();

            $sessionDate = $approvedLeave
                ? $approvedLeave->leave_date->format('Y-m-d')
                : fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d');

            $factory = ($approvedLeave && $approvedLeave->replacement_teacher_id)
                ? TeachingJournal::factory()->substitute($approvedLeave->replacement_teacher_id, $approvedLeave->id)
                : TeachingJournal::factory();

            $factory->create([
                'teacher_id' => $schedule->teacher_id,
                'class_id' => $schedule->class_id,
                'class_schedule_id' => $schedule->schedule_id,
                'session_date' => $sessionDate,
                'material_id' => $materialId,
            ]);
        }
    }
}