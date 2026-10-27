<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\TeachingJournal;
use Illuminate\Database\Seeder;

/**
 * Membuat pertemuan (class_sessions) dari jurnal mengajar yang sudah ada,
 * lalu menautkan tiap jurnal ke pertemuannya (teaching_journals.class_session_id).
 *
 * Jalankan SETELAH TeachingJournalSeeder. Aman dijalankan ulang (updateOrCreate).
 * Pertemuan masa depan (Scheduled) belum dibuat di sini; itu tugas service pembangkit pertemuan.
 */
class ClassSessionSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = ClassSchedule::all()->keyBy('id');

        TeachingJournal::orderBy('class_id')
            ->orderBy('session_date')
            ->orderBy('id')
            ->get(['id', 'class_id', 'class_schedule_id', 'teacher_id', 'session_date', 'class_status'])
            ->groupBy('class_id')
            ->each(function ($journals, $classId) use ($schedules) {
                $number = 0;

                foreach ($journals as $journal) {
                    $schedule = $schedules->get($journal->class_schedule_id);

                    $status = match ($journal->class_status) {
                        'Conducted' => 'Completed',
                        default     => 'Cancelled', // Cancelled & Rescheduled
                    };

                    $session = ClassSession::updateOrCreate(
                        ['class_id' => $classId, 'session_number' => ++$number],
                        [
                            'class_schedule_id' => $journal->class_schedule_id,
                            'kind'              => 'Regular',
                            'session_date'      => $journal->session_date,
                            'start_time'        => $schedule?->start_time ?? '00:00:00',
                            'end_time'          => $schedule?->end_time ?? '00:00:00',
                            'room'              => $schedule?->room,
                            'teacher_id'        => $journal->teacher_id,
                            'location_type'     => 'Studio',
                            'status'            => $status,
                            'change_reason'     => $journal->class_status === 'Rescheduled' ? 'Dijadwalkan ulang' : null,
                        ]
                    );

                    // whereKey()->update() supaya tidak bergantung pada $fillable TeachingJournal
                    TeachingJournal::whereKey($journal->id)->update(['class_session_id' => $session->id]);
                }
            });
    }
}