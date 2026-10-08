<?php

namespace App\Console\Commands;

use App\Models\ClassEnrollment;
use App\Models\PrivateBooking;
use App\Notifications\StudentNotice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Kirim notifikasi ke siswa untuk kelas yang akan mulai dalam ~1–2 jam.
 * Jalankan tiap jam (lihat routes/console.php). Aman dijalankan ulang:
 * pengingat yang sama tidak dikirim dua kali (kolom data->key).
 */
class SendClassReminders extends Command
{
    protected $signature = 'haoyou:class-reminders';

    protected $description = 'Kirim pengingat kelas (reguler & privat) yang akan segera dimulai';

    private const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function handle(): int
    {
        $from = now()->addHour();
        $to   = now()->addHours(2);
        $sent = 0;

        // --- Kelas reguler ---
        $enrollments = ClassEnrollment::with(['student.user', 'class.schedules', 'class.teacher'])
            ->where('status', 'Active')
            ->whereNotNull('class_id')
            ->get();

        foreach ($enrollments as $enrollment) {
            $user  = $enrollment->student?->user;
            $class = $enrollment->class;

            if (! $user || ! $class || ! in_array($class->status, ['Open', 'Running'])) {
                continue;
            }

            foreach ($class->schedules as $schedule) {
                if ($schedule->day !== self::HARI[$from->dayOfWeekIso]) {
                    continue;
                }

                $at = Carbon::parse($from->format('Y-m-d') . ' ' . $schedule->start_time);

                if ($at->lt($from) || $at->gte($to)) {
                    continue;
                }

                $key = 'class-' . $schedule->id . '-' . $at->toDateString() . '-u' . $user->id;

                if ($this->alreadySent($user->id, $key)) {
                    continue;
                }

                $lokasi = $class->delivery_mode === 'Online'
                    ? 'Online'
                    : 'Ruang ' . ($schedule->room ?: '-');

                $user->notify(new StudentNotice(
                    'Kelas Segera Dimulai',
                    $class->class_name
                        . ($class->teacher ? ' bersama ' . $class->teacher->name : '')
                        . ' pukul ' . $at->format('H:i') . ' · ' . $lokasi . '.',
                    route('kelassaya.index'),
                    $key
                ));

                $sent++;
            }
        }

        // --- Kelas privat ---
        $bookings = PrivateBooking::with(['student.user', 'teacher'])
            ->where('status', 'Scheduled')
            ->whereDate('session_date', $from->toDateString())
            ->get();

        foreach ($bookings as $b) {
            $user = $b->student?->user;
            $at   = $b->startsAt();

            if (! $user || $at->lt($from) || $at->gte($to)) {
                continue;
            }

            $key = 'private-' . $b->id . '-' . $at->toDateString() . '-' . $at->format('Hi');

            if ($this->alreadySent($user->id, $key)) {
                continue;
            }

            $user->notify(new StudentNotice(
                'Kelas Privat Segera Dimulai',
                'Kelas privat bersama ' . ($b->teacher?->name ?? 'Laoshi') . ' pukul '
                    . $at->format('H:i') . ' · ' . $b->delivery_mode . '.',
                route('booking.index'),
                $key
            ));

            $sent++;
        }

        $this->info("Pengingat terkirim: {$sent}");

        return self::SUCCESS;
    }

    private function alreadySent(int $userId, string $key): bool
    {
        return DB::table('notifications')
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', $userId)
            ->where('data', 'like', '%"key":"' . $key . '"%')
            ->exists();
    }
}
