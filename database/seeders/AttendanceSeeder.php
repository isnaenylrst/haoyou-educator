<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\ClassEnrollment;
use App\Models\TeachingJournal;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap jurnal mengajar mencatat kehadiran siswa yang
     * terdaftar di kelas terkait.
     */
    public function run(): void
    {
        $journals = TeachingJournal::select('id', 'class_id')->get();

        foreach ($journals as $journal) {
            $studentIds = ClassEnrollment::where('class_id', $journal->class_id)->pluck('student_id');

            foreach ($studentIds as $studentId) {
                Attendance::factory()->create([
                    'teaching_journal_id' => $journal->id,
                    'student_id' => $studentId,
                ]);
            }
        }
    }
}
