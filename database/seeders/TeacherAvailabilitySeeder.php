<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherAvailabilitySeeder extends Seeder
{
    /** [hari, jam mulai, jam selesai] */
    private const PROFILES = [
        // Sore hari kerja + Sabtu
        [
            ['Senin', '13:00', '20:00'],
            ['Selasa', '13:00', '20:00'],
            ['Rabu', '13:00', '20:00'],
            ['Kamis', '13:00', '20:00'],
            ['Jumat', '13:00', '20:00'],
            ['Sabtu', '08:00', '17:00'],
        ],
        // Hari kerja siang + Sabtu pagi
        [
            ['Senin', '09:00', '17:00'],
            ['Selasa', '09:00', '17:00'],
            ['Rabu', '09:00', '17:00'],
            ['Kamis', '09:00', '17:00'],
            ['Jumat', '09:00', '17:00'],
            ['Sabtu', '09:00', '15:00'],
        ],
        // Sore-malam, Sabtu penuh, dan Minggu
        [
            ['Selasa', '15:00', '21:00'],
            ['Rabu', '15:00', '21:00'],
            ['Kamis', '15:00', '21:00'],
            ['Sabtu', '08:00', '20:00'],
            ['Minggu', '09:00', '17:00'],
        ],
    ];

    public function run(): void
    {
        $teachers = Teacher::where('status', 'Active')->orderBy('id')->get();

        if ($teachers->isEmpty()) {
            $this->command->error('Belum ada guru aktif. Jalankan TeacherSeeder dulu.');

            return;
        }

        // Pembuat data: pengguna pertama (biasanya admin dari UserSeeder)
        $createdBy = User::orderBy('id')->value('id');

        $created = 0;

        // 1) Profil dasar per guru
        foreach ($teachers->values() as $i => $teacher) {
            $profile = self::PROFILES[$i % count(self::PROFILES)];

            foreach ($profile as [$day, $start, $end]) {
                $this->availability($teacher->id, $day, $start . ':00', $end . ':00', null, $createdBy);
                $created++;
            }
        }

        // 2) Tutup slot kelas yang sudah ada tapi belum tercakup
        $extra = 0;

        ClassModel::whereNotNull('teacher_id')
            ->with('allSchedules')
            ->get()
            ->each(function (ClassModel $class) use ($createdBy, &$extra) {
                foreach ($class->allSchedules as $slot) {
                    $covered = TeacherAvailability::where('teacher_id', $class->teacher_id)
                        ->where('day', $slot->day)
                        ->where('start_time', '<=', $slot->start_time)
                        ->where('end_time', '>=', $slot->end_time)
                        ->exists();

                    if (! $covered) {
                        $this->availability(
                            $class->teacher_id,
                            $slot->day,
                            $slot->start_time,
                            $slot->end_time,
                            'Mengikuti jadwal kelas yang sudah berjalan',
                            $createdBy
                        );
                        $extra++;
                    }
                }
            });

        $this->command->info("Jadwal tersedia guru: {$created} slot dasar untuk {$teachers->count()} guru, {$extra} slot tambahan dari kelas yang sudah ada.");
    }

    private function availability(int $teacherId, string $day, string $start, string $end, ?string $note, ?int $createdBy): void
    {
        TeacherAvailability::updateOrCreate(
            [
                'teacher_id' => $teacherId,
                'day'        => $day,
                'start_time' => $start,
                'end_time'   => $end,
            ],
            [
                'note'       => $note,
                'created_by' => $createdBy,
            ]
        );
    }
}