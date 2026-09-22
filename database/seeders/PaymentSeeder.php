<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $enrollments = ClassEnrollment::with([
            'programPackage:id,price',
            'privatePackage:id,price',
        ])->get();

        foreach ($enrollments as $enrollment) {
            $totalBill = $enrollment->programPackage->price
                ?? $enrollment->privatePackage->price
                ?? null;

            if (! $totalBill) {
                continue;
            }

            $totalBill = (float) $totalBill;
            $startDate = Carbon::parse($enrollment->enrollment_date);

            match (fake()->randomElement(['lunas', 'lunas', 'termin', 'termin', 'belum'])) {
                'lunas' => $this->seedLunas($enrollment->id, $totalBill, $startDate),
                'termin' => $this->seedTermin($enrollment->id, $totalBill, $startDate),
                'belum' => $this->seedBelumBayar($enrollment->id, $totalBill, $startDate),
            };
        }
    }

    /** Bayar lunas sekali di muka. */
    private function seedLunas(int $enrollmentId, float $totalBill, Carbon $startDate): void
    {
        Payment::factory()
            ->installment('Pelunasan', $totalBill, $totalBill, 0, $startDate->toDateString())
            ->create(['enrollment_id' => $enrollmentId]);
    }

    /** DP lalu dicicil beberapa termin. */
    private function seedTermin(int $enrollmentId, float $totalBill, Carbon $startDate): void
    {
        $dpAmount = round($totalBill * 0.3, 2);
        $remaining = $totalBill - $dpAmount;

        Payment::factory()
            ->installment('DP', $totalBill, $dpAmount, $remaining, $startDate->toDateString())
            ->create(['enrollment_id' => $enrollmentId]);

        $terminCount = fake()->numberBetween(1, 3);
        $perTermin = round($remaining / ($terminCount + 1), 2);
        $paymentDate = $startDate->copy();

        for ($i = 1; $i <= $terminCount; $i++) {
            $paymentDate = $paymentDate->copy()->addDays(fake()->numberBetween(20, 40));

            // Jangan sampai tanggal bayar melewati hari ini
            if ($paymentDate->isFuture()) {
                return;
            }

            $remaining = round($remaining - $perTermin, 2);

            Payment::factory()
                ->installment("Termin {$i}", $totalBill, $perTermin, $remaining, $paymentDate->toDateString())
                ->create(['enrollment_id' => $enrollmentId]);
        }

        // Pelunasan sisa terakhir (kadang belum dilunasi — biar realistis)
        if ($remaining > 0 && fake()->boolean(70)) {
            $paymentDate = $paymentDate->copy()->addDays(fake()->numberBetween(20, 40));

            if ($paymentDate->isFuture()) {
                return;
            }

            Payment::factory()
                ->installment('Pelunasan', $totalBill, $remaining, 0, $paymentDate->toDateString())
                ->create(['enrollment_id' => $enrollmentId]);
        }
    }

    /** Sudah ditagih tapi belum bayar sama sekali. */
    private function seedBelumBayar(int $enrollmentId, float $totalBill, Carbon $startDate): void
    {
        Payment::factory()
            ->pending($totalBill, $startDate->toDateString())
            ->create(['enrollment_id' => $enrollmentId]);
    }
}