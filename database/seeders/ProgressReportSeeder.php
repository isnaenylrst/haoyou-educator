<?php

namespace Database\Seeders;

use App\Models\ProgressReport;
use Illuminate\Database\Seeder;

class ProgressReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        ProgressReport::insert([

            [

                'student_id'=>1,
                'class_id'=>1,
                'teacher_id'=>1,
                'template_progress_report_id'=>1,

                'report_date'=>now(),

                'communication'=>90,
                'confidence'=>88,
                'listening'=>92,
                'reading'=>85,
                'writing'=>83,
                'behavior'=>95,

                'homework'=>'Mengerjakan latihan halaman 12-15.',

                'teacher_notes'=>'Perkembangan sangat baik. Tetap latihan speaking di rumah.',

                'pdf_file'=>'progress_reports/report_1.pdf',

                'status'=>'approved',

                'created_at'=>now(),
                'updated_at'=>now(),

            ],

            [

                'student_id'=>2,
                'class_id'=>1,
                'teacher_id'=>1,
                'template_progress_report_id'=>2,

                'report_date'=>now(),

                'communication'=>82,
                'confidence'=>80,
                'listening'=>85,
                'reading'=>78,
                'writing'=>75,
                'behavior'=>90,

                'homework'=>'Menghafalkan kosakata Bab 3.',

                'teacher_notes'=>'Perlu meningkatkan kemampuan membaca Hanzi.',

                'pdf_file'=>'progress_reports/report_2.pdf',

                'status'=>'submitted',

                'created_at'=>now(),
                'updated_at'=>now(),

            ],

        ]);

    }
}