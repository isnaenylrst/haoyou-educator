<?php

namespace App\Services;

use App\Models\ProgressReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProgressReportService
{
    /** Batas pengisian progress report: H + 3 hari. */
    public const GRACE_DAYS = 3;

    /**
     * Progress report pertemuan terakhir harus sudah TAMPIL DI SISWA paling lambat H + 2 hari.
     * Hanya dipakai untuk notifikasi pengingat ke guru (tidak ada penguncian, GRACE_DAYS tidak berubah).
     */
    public const TARGET_DAYS = 2;

    /** Tanggal H+2: report sudah harus tampil di siswa, dihitung dari hari pertemuan terakhir. */
    public static function targetDate(Carbon $lastMeeting): Carbon
    {
        return $lastMeeting->copy()->addDays(self::TARGET_DAYS)->startOfDay();
    }

    /**
     * "H" = hari pertemuan terakhir kelas (jurnal yang ditandai pertemuan terakhir
     * dan terlaksana). Kalau belum ada jurnal seperti itu, pakai tanggal selesai
     * kelas (classes.end_date). Null = belum ada patokan, belum ada batas.
     */
    public static function baseDate(int $classId): ?Carbon
    {
        $last = DB::table('teaching_journals')
            ->where('class_id', $classId)
            ->where('class_status', 'Conducted')
            ->where('is_last_meeting', 1)
            ->max('session_date');

        if ($last) {
            return Carbon::parse($last);
        }

        $end = DB::table('classes')->where('id', $classId)->value('end_date');

        return $end ? Carbon::parse($end) : null;
    }

    /** Batas akhir pengisian (akhir hari H+3), atau perpanjangan dari Kurikulum bila lebih lama. */
    public static function deadline(int $classId, ?ProgressReport $report = null): ?Carbon
    {
        $base = self::baseDate($classId);
        $standard = $base ? $base->copy()->addDays(self::GRACE_DAYS)->endOfDay() : null;

        $extended = ($report && $report->extended_until)
            ? $report->extended_until->copy()->endOfDay()
            : null;

        if ($standard && $extended) {
            return $standard->gt($extended) ? $standard : $extended;
        }

        return $extended ?? $standard;
    }

    public static function isPastDeadline(int $classId, ?ProgressReport $report = null): bool
    {
        $deadline = self::deadline($classId, $report);

        return $deadline !== null && now()->gt($deadline);
    }

    /** Sertifikat otomatis tersedia: ada report yang di-ACC dan kelas sudah selesai. */
    public static function certificateEligible(object $enrollment): bool
    {
        $hasApproved = ProgressReport::where('enrollment_id', $enrollment->id)->approved()->exists();

        if (!$hasApproved) {
            return false;
        }

        if (($enrollment->status ?? null) === 'Completed') {
            return true;
        }

        return DB::table('teaching_journals')
            ->where('class_id', $enrollment->class_id)
            ->where('class_status', 'Conducted')
            ->where('is_last_meeting', 1)
            ->exists();
    }

        /**
     * Isi sertifikat otomatis:
     *  - tanggal mulai   = pertemuan pertama yang terlaksana (fallback tanggal daftar)
     *  - tanggal selesai = pertemuan terakhir yang terlaksana (fallback hari ini)
     *  - nilai Listening / Reading / Writing = dari progress report terbaru yang sudah di-ACC
     *    (kolom listening_score, reading_score, writing_score di tabel progress_reports).
     *    Nilai yang kosong dibiarkan null supaya guru mengisi manual.
     */
    public static function certificateData(object $enrollment): array
    {
        $journals = DB::table('teaching_journals')
            ->where('class_id', $enrollment->class_id)
            ->where('class_status', 'Conducted');

        $first = (clone $journals)->min('session_date');
        $last  = (clone $journals)->max('session_date');

        $start = $first
            ? Carbon::parse($first)
            : (!empty($enrollment->enrollment_date) ? Carbon::parse($enrollment->enrollment_date) : now());

        $end = $last ? Carbon::parse($last) : now();

        $report = ProgressReport::where('enrollment_id', $enrollment->id)
            ->approved()
            ->latest('id')
            ->first();

        $pick = function (string $key) use ($report) {
            $v = $report?->getAttribute($key . '_score');

            return ($v === null || $v === '') ? null : (float) $v;
        };

        return [
            'start'     => $start,
            'end'       => $end,
            'listening' => $pick('listening'),
            'reading'   => $pick('reading'),
            'writing'   => $pick('writing'),
        ];
    }
}