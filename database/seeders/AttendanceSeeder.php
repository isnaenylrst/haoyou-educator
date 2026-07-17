<?php

namespace Database\Seeders;

use App\Models\Attendance;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Attendance::insert([

            [
                'class_schedule_id' => 1,
                'student_id' => 1,
                'teacher_id' => 1,
                'status' => 'present',
                'notes' => 'Hadir tepat waktu.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'class_schedule_id' => 1,
                'student_id' => 2,
                'teacher_id' => 1,
                'status' => 'late',
                'notes' => 'Terlambat 10 menit.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'class_schedule_id' => 2,
                'student_id' => 3,
                'teacher_id' => 2,
                'status' => 'permission',
                'notes' => 'Izin karena sakit.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'class_schedule_id' => 2,
                'student_id' => 4,
                'teacher_id' => 2,
                'status' => 'present',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'class_schedule_id' => 3,
                'student_id' => 5,
                'teacher_id' => 3,
                'status' => 'absent',
                'notes' => 'Tidak hadir tanpa keterangan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);

    }
}