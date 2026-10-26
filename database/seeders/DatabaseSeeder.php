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
            LevelSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,                  // dipindah ke atas: biaya pendaftaran/aktivitas dibaca seeder enrollment & pembayaran
            CurriculumSeeder::class,
            TeacherSeeder::class,
            ProgramSeeder::class,
            ProgramCategoryLevelSeeder::class,
            ProgramPackageSeeder::class,
            PrivatePackageSeeder::class,
            CandidateStudentSeeder::class,
            CandidateStudentAvailableScheduleSeeder::class,
            FollowUpTemplateSeeder::class,
            FollowUpSeeder::class,
            ClassSeeder::class,           
            ClassEnrollmentSeeder::class,
            StudentSeeder::class,
            ClassScheduleSeeder::class, 
            TeacherAvailabilitySeeder::class,
            PaymentSeeder::class,
            MaterialSeeder::class,
            TeacherLeaveSeeder::class,             
            TeachingJournalSeeder::class,
            ClassSessionSeeder::class,             
            AttendanceSeeder::class,
            MaterialVocabSeeder::class,
            TeacherMaterialSeeder::class,
            ProgressReportSeeder::class,
            DocumentTemplateSeeder::class,
            DocumentSeeder::class,
            PrintTemplateSeeder::class,
        ]);
    }
}