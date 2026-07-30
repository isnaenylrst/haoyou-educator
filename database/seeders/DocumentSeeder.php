<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Level;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * DUMMY DATA - dokumen milik guru (CV) dan siswa (perjanjian kursus),
     * diunggah oleh Admin.
     */
    public function run(): void
    {
        $adminLevelId = Level::where('nama_level', 'Admin')->value('id_level');
        $uploaderId = User::where('level_id', $adminLevelId)->value('id');

        $teacherUserIds = Teacher::pluck('user_id');
        $studentUserIds = Student::pluck('user_id');

        foreach ($teacherUserIds as $index => $userId) {
            Document::factory()->create([
                'user_id' => $userId,
                'uploaded_by' => $uploaderId,
                'title' => 'CV Guru #' . ($index + 1),
                'document_type' => 'CV',
                'visibility' => 'Private',
            ]);
        }

        foreach ($studentUserIds as $index => $userId) {
            Document::factory()->create([
                'user_id' => $userId,
                'uploaded_by' => $uploaderId,
                'title' => 'Perjanjian Kursus Siswa #' . ($index + 1),
                'document_type' => 'Agreement',
                'visibility' => 'Student',
            ]);
        }
    }
}
