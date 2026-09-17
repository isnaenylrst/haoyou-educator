<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\Level;
use App\Models\TeacherLeave;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherLeaveSeeder extends Seeder
{
    public function run(): void
    {
        $adminLevelId = Level::where('nama_level', 'Admin')->value('id_level');
        $approverIds = User::where('level_id', $adminLevelId)->pluck('id')->toArray();

        if (empty($approverIds)) {
            $this->command?->warn('Belum ada User dengan level "Admin", TeacherLeaveSeeder dilewati.');
            return;
        }

        $schedulesByTeacher = ClassSchedule::with('class:id,teacher_id')
            ->get()
            ->filter(fn ($schedule) => $schedule->class?->teacher_id)
            ->groupBy(fn ($schedule) => $schedule->class->teacher_id);

        if ($schedulesByTeacher->isEmpty()) {
            $this->command?->warn('Belum ada class_schedules dengan teacher_id terisi, TeacherLeaveSeeder dilewati.');
            return;
        }

        $teacherIds = $schedulesByTeacher->keys()->all();
        $usedCombinations = [];
        $created = 0;
        $attempts = 0;

        while ($created < 6 && $attempts < 30) {
            $attempts++;

            $teacherId = fake()->randomElement($teacherIds);
            $schedule = $schedulesByTeacher[$teacherId]->random();
            $leaveDate = fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d');

            $key = $schedule->id . '|' . $leaveDate;
            if (isset($usedCombinations[$key])) {
                continue; // hindari duplikat (class_schedule_id, leave_date)
            }
            $usedCombinations[$key] = true;

            $teacherLeave = TeacherLeave::factory()->create([
                'teacher_id' => $teacherId,
                'class_schedule_id' => $schedule->id,
                'leave_date' => $leaveDate,
            ]);

            $update = [];

            if ($teacherLeave->status !== 'Pending') {
                $update['approved_by'] = fake()->randomElement($approverIds);
            }

            if ($teacherLeave->status === 'Approved') {
                $replacementCandidates = array_diff($teacherIds, [$teacherId]);
                if ($replacementCandidates) {
                    $update['replacement_teacher_id'] = fake()->randomElement($replacementCandidates);
                }
            }

            if ($update) {
                $teacherLeave->update($update);
            }

            $created++;
        }
    }
}