<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([

            UserSeeder::class,
            ProgramSeeder::class,
            ProgramPriceSeeder::class,
            FollowupTemplateSeeder::class,
            PdfTemplateSeeder::class,
            SopSeeder::class,
            LearningMaterialSeeder::class,
            SyllabusSeeder::class,
            VocabularySeeder::class,
            LessonPlanSeeder::class,
            PptSeeder::class,
            AttendanceSeeder::class,
            TeachingLogSeeder::class,
            ProgressReportSeeder::class,
            ConsultationScheduleSeeder::class,
            LeaveRequestSeeder::class,
            UserNotificationSeeder::class,
            ReviewLogSeeder::class,

        ]);
    }
}