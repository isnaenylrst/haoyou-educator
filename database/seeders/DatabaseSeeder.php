<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([

            // Master
            LevelSeeder::class,
            UserSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class,

            // Academic
            ProgramSeeder::class,
            ProgramPackageSeeder::class,
            ClassSeeder::class,
            ClassScheduleSeeder::class,
            ClassEnrollmentSeeder::class,

            // Learning
            MaterialSeeder::class,
            MaterialVocabSeeder::class,
            TeacherMaterialSeeder::class,

            // Administration
            ProgramSeeder::class,
            TeacherLeaveSeeder::class,

            // Teaching
            TeachingJournalSeeder::class,
            AttendanceSeeder::class,
            ProgressReportSeeder::class,

            // Document
            DocumentTemplateSeeder::class,
            DocumentSeeder::class,
        ]);
    }
}