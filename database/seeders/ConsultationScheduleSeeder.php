<?php

namespace Database\Seeders;

use App\Models\ConsultationSchedule;
use Illuminate\Database\Seeder;

class ConsultationScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        ConsultationSchedule::insert([

            [
                'teacher_id' => 1,
                'curriculum_id' => 1,
                'consultation_date' => now()->addDays(2),
                'topic' => 'Review Lesson Plan Pertemuan 1',
                'result' => null,
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'teacher_id' => 2,
                'curriculum_id' => 1,
                'consultation_date' => now()->subDays(3),
                'topic' => 'Evaluasi Trial Teaching',
                'result' => 'Guru mampu menguasai kelas dengan baik, perlu meningkatkan teknik bertanya kepada siswa.',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'teacher_id' => 3,
                'curriculum_id' => 2,
                'consultation_date' => now()->subDay(),
                'topic' => 'Review PPT Daily Regular',
                'result' => 'PPT sudah sesuai dengan kurikulum, tambahkan latihan speaking pada slide terakhir.',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);

    }
}