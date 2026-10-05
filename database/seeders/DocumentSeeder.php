<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\Level;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $adminLevelId = Level::where('nama_level', 'Admin')->value('id_level');
        $uploaderId = User::where('level_id', $adminLevelId)->value('id');

        if (! $uploaderId) {
            $this->command?->warn('Belum ada user Admin, DocumentSeeder dilewati.');
            return;
        }

        $agreementTemplateId = DocumentTemplate::where('document_type', 'Agreement')->value('id');
        $certificateTemplateId = DocumentTemplate::where('document_type', 'Certificate')->value('id');

        $teacherUserIds = Teacher::pluck('user_id');
        $students = Student::select('id', 'user_id', 'current_level_id')->get();

        if ($students->isEmpty()) {
            $this->command?->warn('Belum ada data Student — pastikan StudentSeeder dijalankan SEBELUM DocumentSeeder. Bagian dokumen siswa (perjanjian/sertifikat/materi privat) akan dilewati.');
        }

        foreach ($teacherUserIds as $index => $userId) {
            Document::factory()->create([
                'user_id' => $userId,
                'uploaded_by' => $uploaderId,
                'title' => 'CV Guru #' . ($index + 1),
                'document_type' => 'CV',
                'visibility' => 'Private',
            ]);
        }

        foreach ($students as $index => $student) {
            Document::factory()->create([
                'user_id' => $student->user_id,
                'uploaded_by' => $uploaderId,
                'document_template_id' => $agreementTemplateId,
                'title' => 'Perjanjian Kursus Siswa #' . ($index + 1),
                'document_type' => 'Agreement',
                'visibility' => 'Student',
            ]);
        }

        $graduatedStudents = $students->filter(fn ($s) => $s->current_level_id)->random(
            min(10, $students->filter(fn ($s) => $s->current_level_id)->count())
        );

        foreach ($graduatedStudents as $student) {
            Document::factory()
                ->certificate($student->current_level_id)
                ->create([
                    'user_id' => $student->user_id,
                    'uploaded_by' => $uploaderId,
                    'document_template_id' => $certificateTemplateId,
                    'title' => 'Sertifikat Kelulusan Level',
                ]);
        }

        // Materi kelas privat (request siswa, diunggah guru)
        if ($students->isNotEmpty()) {
            Document::factory()
                ->privateMaterial()
                ->count(6)
                ->create([
                    'user_id' => $students->random()->user_id,
                    'uploaded_by' => $uploaderId,
                ]);
        }
    }
}