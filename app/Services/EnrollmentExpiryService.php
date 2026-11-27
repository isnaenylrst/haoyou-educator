<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ClassSession;
use Illuminate\Support\Carbon;

class EnrollmentExpiryService
{
    /**
     * Tanggal pertemuan pertama enrollment (pertemuan Regular pertama pada/setelah
     * tanggal daftar; kalau tidak ada, pertemuan Regular paling awal di kelas itu).
     */
    public function firstSessionDate(ClassEnrollment $enrollment): ?Carbon
    {
        if (! $enrollment->class_id) {
            return null;
        }

        $base = ClassSession::where('class_id', $enrollment->class_id)->where('kind', 'Regular');

        $session = (clone $base)
            ->whereDate('session_date', '>=', $enrollment->enrollment_date?->toDateString() ?? now()->toDateString())
            ->orderBy('session_date')
            ->first()
            ?? $base->orderBy('session_date')->first();

        return $session ? Carbon::parse($session->session_date)->startOfDay() : null;
    }

    /**
     * Expired date paket private = pertemuan pertama + 1 bulan kalender.
     * Null untuk paket reguler atau private yang belum punya pertemuan.
     *
     * @param Carbon|null $firstSession  isi kalau pemanggil sudah punya tanggal pertemuan pertama
     */
    public function packageExpiry(ClassEnrollment $enrollment, ?Carbon $firstSession = null): ?Carbon
    {
        if (! $enrollment->private_package_id) {
            return null;
        }

        $first = $firstSession?->copy()->startOfDay() ?? $this->firstSessionDate($enrollment);

        return $first?->copy()->addMonthNoOverflow();
    }

    /** Tanggal kelas terakhir: akhir kelas, atau (data historis tanpa kelas) last_class_date. */
    public function lastClassDate(ClassEnrollment $enrollment): ?Carbon
    {
        $date = $enrollment->class?->end_date ?? $enrollment->last_class_date;

        return $date ? Carbon::parse($date)->startOfDay() : null;
    }

    /**
     * Awal masa cuti dan awal expired untuk satu enrollment.
     * Null kalau belum bisa ditentukan (tidak ada tanggal pertemuan pertama / kelas terakhir).
     *
     * @return array{leave_start: Carbon, expired_start: Carbon, basis: string}|null
     */
    public function windows(ClassEnrollment $enrollment): ?array
    {
        $expiry = $this->packageExpiry($enrollment);

        if ($expiry) {
            return [
                'leave_start'   => $expiry,
                'expired_start' => $expiry->copy()->addMonthNoOverflow(),
                'basis'         => 'private',
            ];
        }

        $end = $this->lastClassDate($enrollment);

        if (! $end) {
            return null;
        }

        return [
            'leave_start'   => $end->copy()->addDay(),
            'expired_start' => $end->copy()->addMonthNoOverflow()->addDay(),
            'basis'         => 'reguler',
        ];
    }

    /**
     * Hari terakhir siswa masih bisa lanjut program (sehari sebelum expired mulai).
     * Dipakai Retention Rate: lewat tanggal ini tanpa lanjut + bayar = expired.
     */
    public function continuationDeadline(ClassEnrollment $enrollment): ?Carbon
    {
        $w = $this->windows($enrollment);

        return $w ? $w['expired_start']->copy()->subDay() : null;
    }
}
