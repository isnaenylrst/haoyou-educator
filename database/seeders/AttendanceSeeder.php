<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\ClassEnrollment;
use App\Models\TeachingJournal;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $journals = TeachingJournal::where('class_status', 'Conducted')
            ->select('id', 'class_id', 'session_date')
            ->get();

        foreach ($journals as $journal) {
            $studentIds = ClassEnrollment::where('class_id', $journal->class_id)
                ->whereIn('status', ['Active', 'Completed'])
                ->where('enrollment_date', '<=', $journal->session_date)
                ->pluck('student_id');

            foreach ($studentIds as $studentId) {
                Attendance::factory()->create([
                    'teaching_journal_id' => $journal->id,
                    'student_id' => $studentId,
                ]);
            }
        }
    }
}