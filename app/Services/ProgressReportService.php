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
     *  - tanggal mulai  = pertemuan pertama yang terlaksana (fallback tanggal daftar)
     *  - tanggal selesai = pertemuan terakhir yang terlaksana (fallback hari ini)
     *  - nilai          = rata-rata nilai latihan dari semua report yang sudah di-ACC
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

        $scores = ProgressReport::where('enrollment_id', $enrollment->id)
            ->approved()
            ->get()
            ->flatMap(fn ($r) => collect($r->content ?? [])->pluck('score'))
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (float) $v);

        return [
            'start' => $start,
            'end'   => $end,
            'score' => $scores->isEmpty() ? null : round($scores->avg(), 1),
        ];
    }
}