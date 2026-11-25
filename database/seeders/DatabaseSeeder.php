<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Support\DummyFile;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DummyFile::clean(['documents', 'ppt', 'progress_reports']);

        $this->call([
            /* ---------------------------------------------------------- */
            /* 1. Master data & konfigurasi                                */
            /* ---------------------------------------------------------- */
            LevelSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,                  // biaya pendaftaran/aktivitas dibaca modul enrollment & pembayaran
            CurriculumSeeder::class,
            TeacherSeeder::class,
            ProgramSeeder::class,
            ProgramCategoryLevelSeeder::class,
            ProgramPackageSeeder::class,
            PrivatePackageSeeder::class,
            FollowUpTemplateSeeder::class,
            // TeacherAvailabilitySeeder::class,
            // MaterialSeeder::class,
            MaterialVocabSeeder::class,
            TeacherMaterialSeeder::class,
            DocumentTemplateSeeder::class,
            PrintTemplateSeeder::class,
            // KurikulumModuleSeeder::class,
            // VocabWordSeeder::class,             // data kosakata Belajar Vocab

            /* ---------------------------------------------------------- */
            /* 2. Siswa ASLI (Data_Siswa.xlsx)                             */
            /*    Butuh: Level, Program*, ProgramPackage, PrivatePackage   */
            /*    CSV: database/seeders/data/riwayat_siswa.csv             */
            /* ---------------------------------------------------------- */
            RiwayatSiswaSeeder::class,

            /* ---------------------------------------------------------- */
            /* 3. Data dummy: DIMATIKAN                                    */
            /*    Semuanya bergantung pada siswa/kelas dummy. Kalau         */
            /*    dinyalakan, data dummy bercampur dengan siswa asli.       */
            /*    Hapus komentar hanya untuk demo/testing di database lain. */
            /* ---------------------------------------------------------- */
            // CandidateStudentSeeder::class,
            // CandidateStudentAvailableScheduleSeeder::class,
            // FollowUpSeeder::class,
            // ClassSeeder::class,
            // ClassEnrollmentSeeder::class,
            // StudentSeeder::class,               // diganti RiwayatSiswaSeeder
            // StudentPointSeeder::class,
            // ClassScheduleSeeder::class,
            // PaymentSeeder::class,
            // TeacherLeaveSeeder::class,
            // TeachingJournalSeeder::class,
            // ClassSessionSeeder::class,
            // AttendanceSeeder::class,
            // ProgressReportSeeder::class,
            // DocumentSeeder::class,
        ]);
    }
}