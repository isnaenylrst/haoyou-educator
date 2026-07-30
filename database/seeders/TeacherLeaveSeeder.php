<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\Level;
use App\Models\Teacher;
use App\Models\TeacherLeave;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherLeaveSeeder extends Seeder
{
    /**
     * DUMMY DATA - 6 pengajuan cuti guru, dikaitkan ke jadwal kelas terdampak.
     */
    public function run(): void
    {
        $teacherIds = Teacher::pluck('id')->toArray();

        $adminLevelId = Level::where('nama_level', 'Admin')->value('id_level');
        $approverIds = User::where('level_id', $adminLevelId)->pluck('id')->toArray();

        $scheduleIds = ClassSchedule::pluck('id')->toArray();

        for ($i = 0; $i < 6; $i++) {
            $teacherId = fake()->randomElement($teacherIds);

            $teacherLeave = TeacherLeave::factory()->create([
                'teacher_id' => $teacherId,
                'class_schedule_id' => fake()->randomElement($scheduleIds),
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
        }
    }
}
