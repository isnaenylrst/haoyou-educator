<?php

namespace Database\Seeders;

use App\Models\TeachingLog;
use Illuminate\Database\Seeder;

class TeachingLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        TeachingLog::insert([

            [
                'class_schedule_id' => 1,
                'teacher_id' => 1,
                'lesson_plan_id' => 1,
                'summary' => 'Materi Hanzi dasar berhasil disampaikan dengan baik. Sebagian besar siswa aktif mengikuti pembelajaran.',
                'obstacle' => 'Beberapa siswa masih kesulitan menghafal pelafalan Hanzi.',
                'follow_up' => 'Melakukan review kosakata pada pertemuan berikutnya.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'class_schedule_id' => 2,
                'teacher_id' => 1,
                'lesson_plan_id' => 2,
                'summary' => 'Latihan percakapan berjalan lancar dan siswa mampu melakukan dialog sederhana.',
                'obstacle' => 'Masih ada siswa yang kurang percaya diri berbicara.',
                'follow_up' => 'Memberikan latihan speaking tambahan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'class_schedule_id' => 3,
                'teacher_id' => 2,
                'lesson_plan_id' => 3,
                'summary' => 'Materi reading selesai sesuai lesson plan.',
                'obstacle' => 'Waktu pembelajaran kurang karena diskusi cukup panjang.',
                'follow_up' => 'Melanjutkan latihan reading pada pertemuan berikutnya.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);

    }
}