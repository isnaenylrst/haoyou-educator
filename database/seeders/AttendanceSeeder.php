<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\ClassEnrollment;
use App\Models\TeachingJournal;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * DUMMY DATA — tiap jurnal mengajar yang BENAR-BENAR terlaksana
     * (class_status = Conducted) mencatat kehadiran siswa yang:
     * 1. Terdaftar di kelas terkait (via class_id)
     * 2. Enrollment-nya masih Active/Completed (bukan Cancelled/Waiting Class)
     * 3. Sudah bergabung SEBELUM atau PADA tanggal sesi berlangsung
     *    (mencegah siswa "hadir" di sesi sebelum dia resmi terdaftar)
     *
     * Sesi yang Cancelled/Rescheduled sengaja DI-SKIP — tidak masuk akal
     * mencatat presensi untuk kelas yang tidak benar-benar berjalan.
     */
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