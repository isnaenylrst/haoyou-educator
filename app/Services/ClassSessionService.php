<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Membangkitkan pertemuan bertanggal (class_sessions) dari pola mingguan (class_schedules).
 */
class ClassSessionService
{
    public function __construct(private ClassScheduleService $schedules)
    {
    }

    /**
     * Dipakai saat kelas baru dibuat (mis. convert siswa private):
     * bangkitkan $totalMeetings pertemuan mulai start_date kelas, lalu isi end_date
     * dengan tanggal pertemuan terakhir kalau belum terisi.
     */
    public function generateForNewClass(ClassModel $class, int $totalMeetings): Collection
    {
        $sessions = $this->generate($class, $class->start_date, $totalMeetings);

        if ($sessions->isNotEmpty() && ! $class->end_date) {
            $class->update(['end_date' => $sessions->last()->session_date->toDateString()]);
        }

        return $sessions;
    }

    /**
     * Bangkitkan $count pertemuan mulai tanggal $from.
     * Pola yang dipakai adalah pola yang berlaku pada tanggal masing-masing pertemuan
     * (memperhatikan effective_from / effective_until).
     *
     * @param  int|null  $startNumber  nomor pertemuan pertama; null = lanjutkan dari nomor terbesar yang ada
     */
    public function generate(ClassModel $class, Carbon|string $from, int $count, ?int $startNumber = null): Collection
    {
        $created = collect();

        if ($count < 1) {
            return $created;
        }

        $patterns = $class->allSchedules()->orderBy('start_time')->get();

        if ($patterns->isEmpty()) {
            return $created;
        }

        $number = $startNumber ?? ((int) ClassSession::where('class_id', $class->id)->max('session_number') + 1);
        $date   = Carbon::parse($from)->startOfDay();
        $limit  = $date->copy()->addYears(2); // pengaman supaya loop tidak tak terbatas
        $location = $class->delivery_mode === 'Online' ? 'Online' : 'Studio';

        while ($created->count() < $count && $date->lte($limit)) {
            $dayName = ClassScheduleService::DAYS[$date->dayOfWeekIso - 1];

            $todays = $patterns->filter(
                fn (ClassSchedule $p) => $p->day === $dayName && $this->isActiveOn($p, $date)
            );

            foreach ($todays as $pattern) {
                if ($created->count() >= $count) {
                    break;
                }

                if ($class->teacher_id) {
                    $this->schedules->assertTeacherFreeOn(
                        (int) $class->teacher_id,
                        $date,
                        $pattern->start_time,
                        $pattern->end_time,
                        null,
                        $class->id,
                    );
                }

                $created->push(ClassSession::create([
                    'class_id'          => $class->id,
                    'class_schedule_id' => $pattern->id,
                    'kind'              => 'Regular',
                    'session_number'    => $number++,
                    'session_date'      => $date->toDateString(),
                    'start_time'        => $pattern->start_time,
                    'end_time'          => $pattern->end_time,
                    'room'              => $pattern->room,
                    'teacher_id'        => $class->teacher_id,
                    'location_type'     => $location,
                    'status'            => 'Scheduled',
                ]));
            }

            $date->addDay();
        }

        return $created;
    }

    private function isActiveOn(ClassSchedule $pattern, Carbon $date): bool
    {
        $startsOk = ! $pattern->effective_from || $pattern->effective_from->lte($date);
        $endsOk   = ! $pattern->effective_until || $pattern->effective_until->gte($date);

        return $startsOk && $endsOk;
    }
}