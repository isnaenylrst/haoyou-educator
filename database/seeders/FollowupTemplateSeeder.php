<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FollowUpTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $templates = [
            ['template_name' => 'Reminder Jadwal Trial', 'category' => 'Trial', 'description' => 'Mengingatkan calon siswa jadwal kelas trial.'],
            ['template_name' => 'Follow Up Setelah Trial', 'category' => 'Trial', 'description' => 'Menanyakan kesan calon siswa setelah kelas trial.'],
            ['template_name' => 'Penawaran Promo Bulanan', 'category' => 'Promo', 'description' => 'Mengirimkan info promo paket bulan berjalan.'],
            ['template_name' => 'Reminder Pendaftaran', 'category' => 'Pendaftaran', 'description' => 'Mengingatkan calon siswa untuk menyelesaikan pendaftaran.'],
            ['template_name' => 'Follow Up Lead Dingin', 'category' => 'Lead Nurturing', 'description' => 'Menghubungi ulang lead yang sudah lama tidak merespons.'],
        ];

        foreach ($templates as $template) {
            DB::table('follow_up_templates')->insert(array_merge($template, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}