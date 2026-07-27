<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentSeeder extends Seeder
{
    /**
     * Dokumen milik guru (CV, sertifikat) dan siswa (agreement), diunggah
     * oleh Admin.
     */
    public function run(): void
    {
        $now = now();

        $adminLevelId = DB::table('levels')->where('nama_level', 'Admin')->value('id_level');
        $uploaderId = DB::table('users')->where('level_id', $adminLevelId)->value('id');

        $teacherUserIds = DB::table('teachers')->pluck('user_id');
        $studentUserIds = DB::table('students')->pluck('user_id');

        foreach ($teacherUserIds as $index => $userId) {
            DB::table('documents')->insert([
                'user_id' => $userId,
                'title' => 'CV Guru #' . ($index + 1),
                'document_type' => 'CV',
                'description' => fake()->sentence(),
                'file_path' => 'documents/cv_teacher_' . ($index + 1) . '.pdf',
                'visibility' => 'Private',
                'uploaded_by' => $uploaderId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($studentUserIds as $index => $userId) {
            DB::table('documents')->insert([
                'user_id' => $userId,
                'title' => 'Perjanjian Kursus Siswa #' . ($index + 1),
                'document_type' => 'Agreement',
                'description' => fake()->sentence(),
                'file_path' => 'documents/agreement_student_' . ($index + 1) . '.pdf',
                'visibility' => 'Student',
                'uploaded_by' => $uploaderId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}