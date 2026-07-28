<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTemplateSeeder extends Seeder
{
    /**
     * DATA PASTI - daftar template dokumen resmi yang dipakai sistem.
     */
    public function run(): void
    {
        $now = now();

        $adminLevelId = Level::where('nama_level', 'Admin')->value('id_level');
        $uploaderId = User::where('level_id', $adminLevelId)->value('id');

        $templates = [
            ['name' => 'Template Perjanjian Kursus', 'template_type' => 'Agreement'],
            ['name' => 'Template Laporan Progres Siswa', 'template_type' => 'Progress Report'],
            ['name' => 'Template Sertifikat Kelulusan', 'template_type' => 'Certificate'],
            ['name' => 'Template Invoice', 'template_type' => 'Invoice'],
            ['name' => 'Template Pengajuan Cuti Guru', 'template_type' => 'Teacher Leave'],
            ['name' => 'SOP Operasional Kelas', 'template_type' => 'SOP'],
        ];

        foreach ($templates as $index => $template) {
            DB::table('document_templates')->insert([
                'name' => $template['name'],
                'template_type' => $template['template_type'],
                'file_path' => 'document_templates/template_' . ($index + 1) . '.docx',
                'description' => $template['name'] . ' resmi Haoyou Educator.',
                'uploaded_by' => $uploaderId,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
