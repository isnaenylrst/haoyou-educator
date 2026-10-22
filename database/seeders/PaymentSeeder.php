<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\Payment;
use App\Services\PaymentInstallmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(PaymentInstallmentService::class);

        // Eager load relasi lengkap: service butuh installment_price, program, dan level siswa
        $enrollments = ClassEnrollment::with([
            'programPackage.program',
            'privatePackage',
            'student.currentLevel',
        ])->get();

        foreach ($enrollments as $enrollment) {
            $scenario = fake()->randomElement(['lunas', 'lunas', 'termin', 'termin', 'belum']);

            // Paket yang wajib lunas (private, HSK 4 ke atas) tidak boleh dicicil
            if ($scenario === 'termin' && ! $service->enrollmentAllowsInstallment($enrollment)) {
                $scenario = 'lunas';
            }

            // Skema Termin (harga per bulan x lama paket) hanya untuk paket yang punya installment_price.
            // forceFill supaya tetap tersimpan walau 'payment_scheme' belum ada di $fillable.
            $isTerminScheme = $scenario === 'termin' && (bool) $enrollment->programPackage?->installment_price;

            $enrollment->forceFill(['payment_scheme' => $isTerminScheme ? 'Termin' : 'Lunas'])->save();

            // Total tagihan sama persis dengan yang dihitung service
            // (harga paket sesuai skema + biaya pendaftaran + biaya aktivitas - diskon)
            $totalBill = $service->totalBillFor($enrollment);

            if (! $totalBill) {
                continue;
            }

            $startDate = Carbon::parse($enrollment->enrollment_date);

            match ($scenario) {
                'lunas'  => $this->seedLunas($enrollment->id, $totalBill, $startDate),
                'termin' => $this->seedTermin($enrollment->id, $totalBill, $startDate),
                'belum'  => $this->seedBelumBayar($enrollment->id, $totalBill, $startDate),
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