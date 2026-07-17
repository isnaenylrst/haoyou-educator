<?php

namespace Database\Seeders;

use App\Models\TemplateProgressReport;
use Illuminate\Database\Seeder;

class TemplateProgressReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        TemplateProgressReport::insert([

            [
                'name' => 'Daily Regular KT',
                'category' => 'Daily Regular',
                'template_file' => 'templates/daily_regular_kt.pdf',
                'description' => 'Daily Regular (Maochong 1A-6B, Jianer 1A-6B, Hudie 1A-2B, Feixiang 1A-1C) Fokus KT (Mendengar & Berbicara).',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Daily Regular DX',
                'category' => 'Daily Regular',
                'template_file' => 'templates/daily_regular_dx.pdf',
                'description' => 'Daily Regular (Maochong 1A-6B, Jianer 1A-6B, Hudie 1A-2B, Feixiang 1A-1C) Fokus DX (Membaca & Menulis).',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'HSK Level 1-4',
                'category' => 'HSK',
                'template_file' => 'templates/hsk.pdf',
                'description' => 'Template Progress Report untuk kelas HSK Level 1 sampai Level 4.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Private (Bimbel / HSK)',
                'category' => 'Private',
                'template_file' => 'templates/private.pdf',
                'description' => 'Template Progress Report kelas Private (Bimbel maupun HSK).',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Mandarin Bisnis',
                'category' => 'Bisnis',
                'template_file' => 'templates/business.pdf',
                'description' => 'Template Progress Report kelas Mandarin Bisnis.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Mandarin Tradisional / TOCFL',
                'category' => 'Mandarin Tradisional / TOCFL',
                'template_file' => 'templates/tocfl.pdf',
                'description' => 'Template Progress Report kelas Mandarin Tradisional / TOCFL.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);

    }
}