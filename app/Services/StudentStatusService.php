<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Carbon;

/**
 * Status siswa mengikuti kondisi kelas:
 *   Active    (Aktif) : masih punya kelas berjalan / menunggu kelas, atau paket private belum expired
 *   Inactive  (Cuti)  : masa cuti berjalan (hitungan masa cuti ada di EnrollmentExpiryService)
 *   Graduated (Lulus) : masa cuti habis (expired)
 *
 * Private : cuti mulai di expired date paket (pertemuan pertama + 1 bulan), expired 1 bulan setelahnya.
 * Reguler : cuti mulai sehari setelah kelas berakhir, 1 bulan, lalu expired.
 */
class StudentStatusService
{
    /** @deprecated Tidak dipakai lagi untuk status; aturan ada di EnrollmentExpiryService. */
    public const GRACE_DAYS = 30;

    public function __construct(private ?EnrollmentExpiryService $expiry = null)
    {
    }

    private function expiry(): EnrollmentExpiryService
    {
        return $this->expiry ??= new EnrollmentExpiryService();
    }

    /** Status yang seharusnya, atau null kalau tidak bisa ditentukan (belum punya riwayat kelas). */
    public function resolve(Student $student): ?string
    {
        $enrollments = $student->enrollments()
            ->with('class:id,status,end_date')
            ->where('status', '!=', 'Cancelled')
            ->get();

        if ($enrollments->isEmpty()) {
            return null;
        }

        $ongoing = $enrollments->contains(function ($e) {
            if ($e->status === 'Waiting Class') {
                return true;
            }

            if ($e->status !== 'Active') {
                return false;
            }

            $class = $e->class;

            if (! $class) {
                return true;
            }

            $end = $class->end_date ? Carbon::parse($class->end_date) : null;

            return in_array($class->status, ['Open', 'Running'], true)
                && (! $end || $end->gte(today()));
        });

        if ($ongoing) {
            return 'Active';
        }

        // Masa cuti dan expired tiap enrollment; yang dipakai adalah enrollment dengan expired paling akhir
        $latest = $enrollments
            ->map(fn ($e) => $this->expiry()->windows($e))
            ->filter()
            ->sortByDesc(fn ($w) => $w['expired_start']->timestamp)
            ->first();

        if (! $latest) {
            return null;
        }

        $today = today();

        if ($today->gte($latest['expired_start'])) {
            return 'Graduated';
        }

        // Private: sebelum expired date paket siswa masih Aktif walau semua pertemuan sudah selesai
        return $today->gte($latest['leave_start']) ? 'Inactive' : 'Active';
    }

    /** Sinkronkan satu siswa. Mengembalikan true kalau statusnya berubah. */
    public function sync(Student $student): bool
    {
        $status = $this->resolve($student);

        if ($status === null || $status === $student->status) {
            return false;
        }

        $student->forceFill(['status' => $status])->save();

        return true;
    }

    /** Sinkronkan semua siswa. @return array{checked:int, changed:int} */
    public function syncAll(): array
    {
        $checked = 0;
        $changed = 0;

        Student::query()->chunkById(200, function ($students) use (&$checked, &$changed) {
            foreach ($students as $student) {
                $checked++;

                if ($this->sync($student)) {
                    $changed++;
                }
            }
        });

        return ['checked' => $checked, 'changed' => $changed];
    }
}