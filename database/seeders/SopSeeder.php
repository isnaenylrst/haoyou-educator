<?php

namespace Database\Seeders;

use App\Models\Sop;
use Illuminate\Database\Seeder;

class SopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sop::create([
            'curriculum_id' => 1,
            'title' => 'SOP Trial Teaching',
            'description' => 'Panduan pelaksanaan Trial Teaching bagi guru baru.',
            'version' => '1.0',
            'file_path' => 'sops/trial_teaching.pdf',
            'status' => 'published',
        ]);

        Sop::create([
            'curriculum_id' => 1,
            'title' => 'SOP Daily Regular',
            'description' => 'Panduan mengajar kelas Daily Regular.',
            'version' => '1.0',
            'file_path' => 'sops/daily_regular.pdf',
            'status' => 'published',
        ]);

        Sop::create([
            'curriculum_id' => 1,
            'title' => 'SOP Online Class',
            'description' => 'Panduan pembelajaran kelas online.',
            'version' => '1.0',
            'file_path' => 'sops/online_class.pdf',
            'status' => 'published',
        ]);

        Sop::create([
            'curriculum_id' => 1,
            'title' => 'SOP Placement Test',
            'description' => 'Panduan pelaksanaan placement test.',
            'version' => '1.0',
            'file_path' => 'sops/placement_test.pdf',
            'status' => 'draft',
        ]);

        Sop::create([
            'curriculum_id' => 1,
            'title' => 'SOP Evaluasi Guru',
            'description' => 'Panduan evaluasi performa guru.',
            'version' => '1.0',
            'file_path' => 'sops/evaluasi_guru.pdf',
            'status' => 'published',
        ]);
    }
}