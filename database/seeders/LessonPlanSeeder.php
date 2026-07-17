<?php

namespace Database\Seeders;

use App\Models\LessonPlan;
use Illuminate\Database\Seeder;

class LessonPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        LessonPlan::insert([

            [
                'class_schedule_id'=>1,
                'teacher_id'=>1,
                'learning_material_id'=>1,
                'title'=>'Lesson Plan Pertemuan 1',
                'objective'=>'Siswa mampu memperkenalkan diri menggunakan Bahasa Mandarin.',
                'activity'=>'Ice Breaking, Pengenalan Hanzi, Percakapan, Latihan Berpasangan.',
                'assessment'=>'Observasi dan praktik berbicara.',
                'status'=>'approved',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            [
                'class_schedule_id'=>2,
                'teacher_id'=>1,
                'learning_material_id'=>2,
                'title'=>'Lesson Plan Pertemuan 2',
                'objective'=>'Siswa memahami percakapan sederhana.',
                'activity'=>'Review materi, Listening, Speaking Practice.',
                'assessment'=>'Quiz lisan.',
                'status'=>'submitted',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            [
                'class_schedule_id'=>3,
                'teacher_id'=>2,
                'learning_material_id'=>3,
                'title'=>'Lesson Plan Reading',
                'objective'=>'Siswa mampu membaca kalimat sederhana.',
                'activity'=>'Reading Practice dan Diskusi.',
                'assessment'=>'Tes membaca.',
                'status'=>'draft',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

        ]);

    }
}