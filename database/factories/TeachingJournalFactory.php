<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TeachingJournalFactory extends Factory
{
    public function definition(): array
    {
        $classStatus = fake()->randomElement(['Conducted', 'Conducted', 'Conducted', 'Cancelled', 'Rescheduled']);

        return [
            'is_substitute' => false,
            'substitute_teacher_id' => null,
            'teacher_leave_id' => null,
            'class_status' => $classStatus,
            'learning_activities' => $this->activitiesFor($classStatus),
            'problems' => $classStatus === 'Conducted' ? fake()->optional()->sentence() : null,
            'solutions' => $classStatus === 'Conducted' ? fake()->optional()->sentence() : null,
            'results' => $classStatus === 'Conducted' ? fake()->optional()->sentence() : null,
            'notes' => $this->notesFor($classStatus),
        ];
    }
    public function substitute(int $substituteTeacherId, ?int $teacherLeaveId = null): static
    {
        return $this->state([
            'is_substitute' => true,
            'substitute_teacher_id' => $substituteTeacherId,
            'teacher_leave_id' => $teacherLeaveId,
            'class_status' => 'Conducted', // guru pengganti hadir, kelas tetap jalan
        ]);
    }

    private function activitiesFor(string $status): string
    {
        return match ($status) {
            'Conducted' => fake()->paragraph(),
            'Cancelled' => 'Kelas dibatalkan, tidak ada aktivitas pembelajaran.',
            'Rescheduled' => 'Kelas dijadwal ulang, sesi ini belum terlaksana.',
            default => fake()->paragraph(),
        };
    }

    private function notesFor(string $status): ?string
    {
        return match ($status) {
            'Cancelled' => fake()->randomElement([
                'Guru berhalangan hadir dan tidak ada pengganti',
                'Seluruh siswa berhalangan hadir',
                'Force majeure (cuaca/listrik padam)',
            ]),
            'Rescheduled' => fake()->randomElement([
                'Dipindah atas kesepakatan siswa',
                'Dipindah karena bentrok jadwal ruangan',
            ]),
            default => fake()->optional()->sentence(),
        };
    }
}