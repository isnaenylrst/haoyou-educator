<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentInstallmentService
{
    /**
     * Catat SATU transaksi termin (Cara B) — selalu bikin baris Payment
     * baru, tidak pernah update baris lama. remaining_bill dihitung
     * kumulatif dari total_bill dikurangi SEMUA amount_paid yang sudah
     * tercatat sebelumnya untuk enrollment ini, ditambah transaksi ini.
     */
    public function recordPayment(
        ClassEnrollment $enrollment,
        string $stage,
        float $amount,
        string $paymentMethod,
        ?string $paymentDate = null,
        ?string $proofPath = null,
    ): Payment {
        $totalBill = $this->totalBillFor($enrollment);

        if (! $totalBill) {
            throw ValidationException::withMessages([
                'enrollment_id' => 'Enrollment ini tidak punya paket (program/private) yang valid, tidak bisa hitung tagihan.',
            ]);
        }

        $date = $paymentDate ? Carbon::parse($paymentDate) : now();
        $enrollmentDate = Carbon::parse($enrollment->enrollment_date);

        if ($date->isFuture()) {
            throw ValidationException::withMessages([
                'payment_date' => 'Tanggal bayar tidak boleh di masa depan.',
            ]);
        }

        if ($date->lt($enrollmentDate)) {
            throw ValidationException::withMessages([
                'payment_date' => 'Tanggal bayar tidak boleh sebelum tanggal enrollment (' . $enrollmentDate->toDateString() . ').',
            ]);
        }

        $alreadyPaid = $this->totalPaid($enrollment);
        $remaining = round($totalBill - $alreadyPaid - $amount, 2);

        if ($remaining < 0) {
            $sisaSaatIni = number_format($totalBill - $alreadyPaid, 0, ',', '.');
            throw ValidationException::withMessages([
                'amount_paid' => "Jumlah bayar melebihi sisa tagihan (Rp{$sisaSaatIni}).",
            ]);
        }

        if ($remaining > 0 && ! $this->enrollmentAllowsInstallment($enrollment)) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Program/paket ini wajib dibayar lunas, tidak bisa dicicil.',
            ]);
        }

        return Payment::create([
            'enrollment_id' => $enrollment->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'payment_stage' => $stage,
            'total_bill' => $totalBill,
            'amount_paid' => $amount,
            'remaining_bill' => $remaining,
            'payment_method' => $paymentMethod,
            'payment_date' => $date->toDateString(),
            'payment_proof_path' => $proofPath,
            'status' => $remaining <= 0 ? 'Paid' : 'Partial',
        ]);
    }

    /**
     * Total tagihan enrollment = harga paket (sudah total, bukan per bulan)
     * + biaya pendaftaran + biaya aktivitas - diskon.
     */
    public function totalBillFor(ClassEnrollment $enrollment): ?float
    {
        $packageTotal = $this->packageTotal($enrollment);

        if ($packageTotal === null) {
            return null;
        }

        $total = $packageTotal
            + (float) $enrollment->registration_fee
            + (float) $enrollment->activity_fee
            - (float) $enrollment->discount;

        return max($total, 0);
    }

    /** Lama kelas (bulan), hanya informasi. Tidak dipakai untuk mengalikan harga. */
    public function monthsFor(ClassEnrollment $e): int
    {
        $package = $e->programPackage ?? $e->privatePackage;

        return $this->monthsForPackage($package, ! $e->programPackage);
    }

    /**
     * Total biaya les dari enrollment.
     * Skema Termin (khusus paket reguler yang punya installment_price) = harga per bulan x lama paket.
     * Selain itu = harga paket apa adanya (harga lunas).
     */
    public function packageTotal(ClassEnrollment $e): ?float
    {
        $isPrivate = ! $e->programPackage;
        $package   = $e->programPackage ?? $e->privatePackage;

        if (! $isPrivate && $e->payment_scheme === 'Termin' && $package?->installment_price) {
            return $package->totalFor('Termin');
        }

        return $this->packageTotalFor($package, $isPrivate);
    }

    /**
     * Lama kelas (bulan) dari paketnya langsung. Dipakai juga saat konversi calon siswa,
     * ketika enrollment belum dibuat. Membaca kolom duration_months; fallback 3 (program) / 1 (private).
     */
    public function monthsForPackage(mixed $package, bool $isPrivate): int
    {
        return (int) ($package?->duration_months ?: ($isPrivate ? 1 : 3));
    }

    /**
     * Total biaya les dari paketnya langsung = harga paket apa adanya (tidak dikali bulan).
     * Parameter $isPrivate dipertahankan supaya pemanggil lama tidak error.
     */
    public function packageTotalFor(mixed $package, bool $isPrivate): ?float
    {
        if (! $package || $package->price === null) {
            return null;
        }

        return (float) $package->price;
    }

    public function allowsInstallment(bool $isPrivate, ?string $programName, ?string $levelName): bool
    {
        if ($isPrivate) return false;
        if ($programName === 'Daily Activity') return true;
        if (! str_starts_with((string) $levelName, 'HSK ')) return false;

        $level = (int) trim(substr($levelName, 4));

        return $level >= 1 && $level <= 3;
    }

    public function enrollmentAllowsInstallment(ClassEnrollment $e): bool
    {
        return $this->allowsInstallment(
            (bool) $e->private_package_id,
            $e->programPackage?->program?->program_name,
            $e->student?->currentLevel?->level_name,
        );
    }

    public function totalPaid(ClassEnrollment $enrollment): float
    {
        return (float) $enrollment->payments()->sum('amount_paid');
    }

    public function remainingBill(ClassEnrollment $enrollment): float
    {
        $totalBill = $this->totalBillFor($enrollment) ?? 0.0;

        return max($totalBill - $this->totalPaid($enrollment), 0);
    }

    public function isFullyPaid(ClassEnrollment $enrollment): bool
    {
        return $this->remainingBill($enrollment) <= 0;
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Payment::where('invoice_number', $number)->exists());

        return $number;
    }
}