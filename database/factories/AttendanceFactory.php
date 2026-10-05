<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement([
            'Present', 'Present', 'Present', 'Present', 'Present',
            'Present', 'Present', 'Present', 'Present', 'Present',
            'Present', 'Present', 'Present', 'Present', 'Present',
            'Sick', 'Sick',
            'Permission', 'Permission',
            'Absent',
        ]);

        return [
            'status' => $status,
            'note' => $this->noteFor($status),
        ];
    }

    private function noteFor(string $status): ?string
    {
        return match ($status) {
            'Sick' => fake()->randomElement([
                'Demam, izin dari orang tua lewat WhatsApp',
                'Sedang flu, tidak masuk sesuai info orang tua',
                'Sakit perut, sudah dikabari orang tua pagi ini',
                'Demam tinggi semalam, istirahat di rumah',
            ]),
            'Permission' => fake()->randomElement([
                'Ada acara keluarga',
                'Ikut acara sekolah',
                'Pulang kampung bersama keluarga',
                'Ada janji dokter gigi',
            ]),
            'Absent' => fake()->optional(0.5)->randomElement([
                'Belum ada kabar dari orang tua',
                'Tidak ada konfirmasi sampai kelas dimulai',
            ]),
            default => null, // Present biasanya tanpa catatan
        };
    }
}