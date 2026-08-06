<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LevelSeeder::class,
            UserSeeder::class,
            CurriculumSeeder::class,
            TeacherSeeder::class,
            ProgramSeeder::class,
            ProgramPackageSeeder::class,            
            CandidateStudentSeeder::class,
            CandidateStudentAvailableScheduleSeeder::class,
            StudentSeeder::class,
            FollowUpTemplateSeeder::class,
            FollowUpSeeder::class,
            ClassSeeder::class,
            ClassScheduleSeeder::class,
            ClassEnrollmentSeeder::class,
            PaymentSeeder::class,
            MaterialSeeder::class,
            TeachingJournalSeeder::class,
            AttendanceSeeder::class,
            MaterialVocabSeeder::class,
            TeacherMaterialSeeder::class,
            TeacherLeaveSeeder::class,
            ProgressReportSeeder::class,
            DocumentTemplateSeeder::class,
            DocumentSeeder::class,
        ]);
    }
}