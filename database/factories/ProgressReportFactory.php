<?php

namespace Database\Factories;

use Database\Seeders\Support\DummyFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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

        return [
            'report_type' => $reportType,
            'report_period' => $this->periodFor($reportType),
            'file_path' => DummyFile::store('progress_reports', 'sample.pdf'),
            'status' => fake()->randomElement(['Draft', 'Submitted', 'Submitted']),
        ];
    }

    /**
     * Periode laporan disesuaikan tipe & selalu di MASA LALU (tidak mungkin
     * ada laporan progres untuk bulan yang belum terjadi). Nama bulan pakai
     * bahasa Indonesia supaya konsisten dengan report_type-nya.
     */
    private function periodFor(string $reportType): string
    {
        $date = Carbon::parse(fake()->dateTimeBetween('-5 months', 'now'));
        $monthName = self::MONTHS_ID[$date->month];

        return $reportType === 'Bulanan'
            ? "{$monthName} {$date->year}"
            : "Selesai {$monthName} {$date->year}";
    }
}