<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\Material;
use App\Models\TeacherLeave;
use App\Models\TeachingJournal;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Jurnal mengajar dummy: satu jurnal per pertemuan mingguan, tepat di hari jadwalnya,
 * dari 2 bulan lalu (atau tanggal mulai kelas) sampai hari ini.
 *
 * Jalankan SETELAH: ClassSeeder, ClassScheduleSeeder, MaterialSeeder, TeacherLeaveSeeder.
 * Jalankan SEBELUM: ClassSessionSeeder dan AttendanceSeeder.
 */
class TeachingJournalSeeder extends Seeder
{
    private const ISO_DAY = [
        'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4,
        'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7,
    ];

    public function run(): void
    {
        $today       = Carbon::today();
        $windowStart = $today->copy()->subMonths(2);

        $schedules = ClassSchedule::join('classes', 'classes.id', '=', 'class_schedules.class_id')
            ->whereNotNull('classes.teacher_id')
            ->select(
                'class_schedules.id as schedule_id',
                'class_schedules.day',
                'classes.id as class_id',
                'classes.teacher_id',
                'classes.level_id',
                'classes.start_date',
                'classes.end_date'
            )
            ->get();

        foreach ($schedules as $schedule) {
            $isoDay = self::ISO_DAY[$schedule->day] ?? null;

            if (! $isoDay) {
                continue;
            }

            // Rentang tanggal: dari mulai kelas (maks 2 bulan lalu) sampai selesai kelas / hari ini
            $from  = $schedule->start_date
                ? Carbon::parse($schedule->start_date)->startOfDay()->max($windowStart)
                : $windowStart->copy();
            $until = $schedule->end_date
                ? Carbon::parse($schedule->end_date)->startOfDay()->min($today)
                : $today->copy();

            if ($from->gt($until)) {
                continue;
            }

            // Cuti yang disetujui untuk jadwal ini, dikunci per tanggal
            $leaves = TeacherLeave::where('class_schedule_id', $schedule->schedule_id)
                ->where('status', 'Approved')
                ->get()
                ->keyBy(fn ($leave) => $leave->leave_date->format('Y-m-d'));

            $materialIds = $schedule->level_id
                ? Material::where('level_id', $schedule->level_id)->pluck('id')
                : collect();

            $made = [];

            // Hari pertama yang cocok dengan hari jadwal
            $date = $from->copy();
            while ($date->dayOfWeekIso !== $isoDay) {
                $date->addDay();
            }

            for (; $date->lte($until); $date->addWeek()) {
                $key = $date->format('Y-m-d');
                $this->createJournal($schedule, $key, $leaves->get($key), $materialIds);
                $made[$key] = true;
            }

            // Cuti yang tanggalnya tidak jatuh tepat di hari jadwal tetap dibuatkan jurnal pengganti
            foreach ($leaves as $key => $leave) {
                if (isset($made[$key]) || Carbon::parse($key)->gt($today)) {
                    continue;
                }

                $this->createJournal($schedule, $key, $leave, $materialIds);
            }
        }
    }

    private function createJournal($schedule, string $sessionDate, ?TeacherLeave $leave, $materialIds): void
    {
        $factory = ($leave && $leave->replacement_teacher_id)
            ? TeachingJournal::factory()->substitute($leave->replacement_teacher_id, $leave->id)
            : TeachingJournal::factory();

        $factory->create([
            'teacher_id'        => $schedule->teacher_id,
            'class_id'          => $schedule->class_id,
            'class_schedule_id' => $schedule->schedule_id,
            'session_date'      => $sessionDate,
            'material_id'       => $materialIds->isNotEmpty() ? $materialIds->random() : null,
        ]);
    }
}