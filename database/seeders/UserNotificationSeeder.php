<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserNotification;

class UserNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        UserNotification::insert([

            [

                'user_id' => 1,

                'title' => 'Lesson Plan Disetujui',

                'message' => 'Lesson Plan Pertemuan 1 telah disetujui oleh Kepala Kurikulum.',

                'type' => 'lesson_plan',

                'is_read' => false,

                'created_at' => now(),

                'updated_at' => now(),

            ],

            [

                'user_id' => 2,

                'title' => 'PPT Direvisi',

                'message' => 'Silakan lakukan revisi PPT sesuai catatan Kepala Kurikulum.',

                'type' => 'ppt',

                'is_read' => false,

                'created_at' => now(),

                'updated_at' => now(),

            ],

            [

                'user_id' => 3,

                'title' => 'Jadwal Konsultasi',

                'message' => 'Anda memiliki jadwal konsultasi pada tanggal 20 Juli 2026.',

                'type' => 'consultation',

                'is_read' => true,

                'created_at' => now(),

                'updated_at' => now(),

            ],

            [

                'user_id' => 1,

                'title' => 'Progress Report',

                'message' => 'Progress Report siswa berhasil dikirim.',

                'type' => 'progress_report',

                'is_read' => true,

                'created_at' => now(),

                'updated_at' => now(),

            ],

            [

                'user_id' => 2,

                'title' => 'Pengajuan Izin',

                'message' => 'Pengajuan izin Anda telah disetujui.',

                'type' => 'leave_request',

                'is_read' => false,

                'created_at' => now(),

                'updated_at' => now(),

            ],

        ]);

    }
}