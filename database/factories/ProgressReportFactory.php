<?php

namespace Database\Factories;

use App\Models\ProgressReport;
use Database\Seeders\Support\DummyFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * Tahap proses laporan (sama dengan ProgressReportController::PROSES):
 *  - Draft            : status Draft, returned_at kosong
 *  - Dikembalikan     : status Draft, returned_at terisi (dikembalikan Kurikulum)
 *  - Menunggu Review  : status Submitted, reviewed_at kosong
 *  - Disetujui        : status Submitted, reviewed_at terisi (tampil di "Laporan Terbit")
 */
class ProgressReportFactory extends Factory
{
    private const MONTHS_ID = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function definition(): array
    {
        $reportType = fake()->randomElement(['Bulanan', 'Bulanan', 'Akhir Paket']);
        $date = Carbon::parse(fake()->dateTimeBetween('-3 months', 'now'));

        $stage = match (fake()->randomElement(['draft', 'returned', 'pending', 'approved'])) {
            'draft'    => self::draftAttributes(),
            'returned' => self::returnedAttributes($date),
            'pending'  => self::pendingAttributes($date),
            default    => self::approvedAttributes($date, null),
        };

        return array_merge([
            'report_type'   => $reportType,
            'report_period' => self::periodLabel($reportType, $date),
        ], $stage);
    }

    /** Draft: belum ada file, belum disubmit. */
    public function draft(): static
    {
        return $this->state(fn () => self::draftAttributes());
    }

    /** Dikembalikan Kurikulum untuk diperbaiki laoshi. */
    public function returned(?Carbon $at = null): static
    {
        return $this->state(fn () => self::returnedAttributes($at ?? now()));
    }

    /** Sudah dikirim laoshi, menunggu review Kurikulum. */
    public function pendingReview(?Carbon $at = null): static
    {
        return $this->state(fn () => self::pendingAttributes($at ?? now()));
    }

    /** Sudah disetujui Kurikulum (muncul di "Laporan Terbit", bisa dilihat/diunduh). */
    public function approved(?Carbon $at = null, ?int $reviewerId = null): static
    {
        return $this->state(fn () => self::approvedAttributes($at ?? now(), $reviewerId));
    }

    /**
     * Label periode (bahasa Indonesia): Bulanan -> "Juli 2026", Akhir Paket -> "Selesai Juli 2026".
     */
    public static function periodLabel(string $reportType, Carbon $date): string
    {
        $monthName = self::MONTHS_ID[$date->month];

        return $reportType === 'Bulanan'
            ? "{$monthName} {$date->year}"
            : "Selesai {$monthName} {$date->year}";
    }

    private static function draftAttributes(): array
    {
        return [
            'status'       => 'Draft',
            'file_path'    => null,
            'submitted_at' => null,
            'returned_at'  => null,
            'reviewed_at'  => null,
            'reviewed_by'  => null,
            'published_at' => null,
            'review_note'  => null,
        ];
    }

    private static function returnedAttributes(Carbon $at): array
    {
        return array_merge(self::draftAttributes(), [
            'returned_at' => $at->copy()->min(now())->toDateTimeString(),
            'review_note' => fake()->randomElement([
                'Mohon lengkapi catatan perkembangan keterampilan menulis.',
                'Nilai belum sesuai dengan jurnal mengajar, mohon dicek lagi.',
                'Tambahkan saran belajar di rumah untuk orang tua.',
            ]),
        ]);
    }

    private static function pendingAttributes(Carbon $at): array
    {
        $total = 12;

        return [
            'status'             => ProgressReport::SUBMITTED,
            'file_path'          => DummyFile::store('progress_reports', 'sample.pdf'),
            'submitted_at'       => $at->copy()->min(now())->toDateTimeString(),
            'returned_at'        => null,
            'reviewed_at'        => null,
            'reviewed_by'        => null,
            'published_at'       => null,
            'review_note'        => null,
            'attendance_total'   => $total,
            'attendance_present' => fake()->numberBetween(7, $total),
            'listening_score'    => fake()->numberBetween(65, 95),
            'reading_score'      => fake()->numberBetween(65, 95),
            'writing_score'      => fake()->numberBetween(60, 92),
        ];
    }

    private static function approvedAttributes(Carbon $at, ?int $reviewerId): array
    {
        $reviewedAt = $at->copy()->addDays(2)->min(now())->toDateTimeString();

        return array_merge(self::pendingAttributes($at), [
            'reviewed_at'  => $reviewedAt,
            'reviewed_by'  => $reviewerId,
            'published_at' => $reviewedAt,
        ]);
    }
}