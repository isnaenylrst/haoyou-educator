<?php

namespace App\Http\Controllers\Siswa\Concerns;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait HandlesStudent
{
    /** Data siswa dari user yang sedang login (students.user_id). */
    protected function student(): Student
    {
        $student = Auth::user()?->student;

        abort_if(! $student, 403, 'Data siswa tidak ditemukan.');

        return $student;
    }

    /** Nama hari Indonesia, sama dengan isi class_schedules.day. */
    protected function hariId(Carbon $date): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            // 7 => 'Minggu',
        ][$date->dayOfWeekIso];
    }

    /** Contoh: "Kamis, 08 Okt". */
    protected function tanggalId(Carbon $date): string
    {
        $bulan = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ][$date->month];

        return $this->hariId($date) . ', ' . $date->format('d') . ' ' . $bulan;
    }
}
