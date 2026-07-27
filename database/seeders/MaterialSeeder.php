<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialSeeder extends Seeder
{
    /**
     * Tiap program_package mendapat 3 materi (meeting 1-3), diunggah oleh
     * user Curriculum. unique(program_package_id, meeting_number) dijaga
     * karena meeting_number diambil berurutan per package.
     */
    public function run(): void
    {
        $now = now();

        $curriculumLevelId = DB::table('levels')->where('nama_level', 'Curriculum')->value('id_level');
        $uploaderIds = DB::table('users')->where('level_id', $curriculumLevelId)->pluck('id')->toArray();

        $packageIds = DB::table('program_packages')->pluck('id');

        $topics = ['Perkenalan & Salam', 'Angka & Waktu', 'Percakapan Sehari-hari'];

        foreach ($packageIds as $packageId) {
            foreach ($topics as $meetingNumber => $topic) {
                DB::table('materials')->insert([
                    'program_package_id' => $packageId,
                    'uploaded_by' => fake()->randomElement($uploaderIds),
                    'meeting_number' => $meetingNumber + 1,
                    'title' => 'Pertemuan ' . ($meetingNumber + 1) . ' - ' . $topic,
                    'syllabus' => fake()->paragraph(),
                    'material_file_path' => 'materials/package_' . $packageId . '_meeting_' . ($meetingNumber + 1) . '.pdf',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}