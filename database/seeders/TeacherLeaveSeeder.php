<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherLeaveSeeder extends Seeder
{
    /**
     * Beberapa pengajuan cuti guru, dikaitkan ke jadwal kelas yang terdampak.
     */
    public function run(): void
    {
        $now = now();

        $teacherIds = DB::table('teachers')->pluck('id')->toArray();

        $adminLevelId = DB::table('levels')->where('nama_level', 'Admin')->value('id_level');
        $approverIds = DB::table('users')->where('level_id', $adminLevelId)->pluck('id')->toArray();

        $scheduleIds = DB::table('class_schedules')->pluck('id')->toArray();

        for ($i = 0; $i < 6; $i++) {
            $teacherId = fake()->randomElement($teacherIds);
            $status = fake()->randomElement(['Pending', 'Approved', 'Approved', 'Rejected']);
            $isDecided = $status !== 'Pending';

            $replacementCandidates = array_diff($teacherIds, [$teacherId]);

            DB::table('teacher_leaves')->insert([
                'teacher_id' => $teacherId,
                'class_schedule_id' => fake()->randomElement($scheduleIds),
                'leave_type' => fake()->randomElement(['Sick', 'Permission']),
                'reason' => fake()->sentence(),
                'supporting_document' => fake()->optional()->randomElement(['leaves/surat_dokter_' . $i . '.pdf']),
                'replacement_teacher_id' => $status === 'Approved' && $replacementCandidates
                    ? fake()->randomElement($replacementCandidates)
                    : null,
                'status' => $status,
                'approved_by' => $isDecided ? fake()->randomElement($approverIds) : null,
                'approved_at' => $isDecided ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}