<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\Payment;
use App\Services\PaymentInstallmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Di aplikasi, enrollment selalu lahir bersama DP (ConvertController -> PaymentInstallmentService),
 * dengan DP minimal Rp300.000 (= biaya pendaftaran). Jadi TIDAK ada enrollment tanpa pembayaran.
 * Seeder ini mengikuti aturan itu: tiap enrollment minimal punya satu pembayaran DP/Pelunasan.
 */
class PaymentSeeder extends Seeder
{
    /** DP minimum = biaya pendaftaran */
    private const MIN_DP = 300000;

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
            // Kelas yang sudah selesai hampir pasti sudah lunas / dicicil; yang lain boleh baru DP saja
            $scenario = $enrollment->status === 'Completed'
                ? fake()->randomElement(['lunas', 'lunas', 'termin', 'termin'])
                : fake()->randomElement(['lunas', 'termin', 'termin', 'dp']);

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
                'dp'     => $this->seedHanyaDp($enrollment->id, $totalBill, $startDate),
            };
        }
    }

    /** DP: minimal biaya pendaftaran, atau 30% tagihan kalau lebih besar (tidak lebih dari total). */
    private function dpAmount(float $totalBill): float
    {
        return min($totalBill, max(self::MIN_DP, round($totalBill * 0.3, 2)));
    }

    /** Bayar lunas sekali di muka. */
    private function seedLunas(int $enrollmentId, float $totalBill, Carbon $startDate): void
    {
        Payment::factory()
            ->installment('Pelunasan', $totalBill, $totalBill, 0, $startDate->toDateString())
            ->create(['enrollment_id' => $enrollmentId]);
    }

    /** Baru DP, sisanya belum dibayar (sudah daftar, belum lunas). */
    private function seedHanyaDp(int $enrollmentId, float $totalBill, Carbon $startDate): void
    {
        $dpAmount = $this->dpAmount($totalBill);

        Payment::factory()
            ->installment('DP', $totalBill, $dpAmount, round($totalBill - $dpAmount, 2), $startDate->toDateString())
            ->create(['enrollment_id' => $enrollmentId]);
    }

    /** DP lalu dicicil beberapa termin. */
    private function seedTermin(int $enrollmentId, float $totalBill, Carbon $startDate): void
    {
        $dpAmount = $this->dpAmount($totalBill);
        $remaining = round($totalBill - $dpAmount, 2);

        Payment::factory()
            ->installment('DP', $totalBill, $dpAmount, $remaining, $startDate->toDateString())
            ->create(['enrollment_id' => $enrollmentId]);

        if ($remaining <= 0) {
            return;
        }

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
}