<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentInstallmentService
{
    /**
     * Catat SATU transaksi termin (Cara B).
     * Selalu membuat baris Payment baru, tidak mengubah transaksi lama.
     * remaining_bill dihitung dari total tagihan dikurangi seluruh
     * pembayaran paket sebelumnya dan pembayaran saat ini.
     *
     * Berjalan dalam transaksi dengan lock pada baris enrollment, supaya dua
     * pembayaran yang masuk bersamaan tidak sama-sama lolos cek sisa tagihan.
     */
    public function recordPayment(
        ClassEnrollment $enrollment,
        string $stage,
        float $amount,
        string $paymentMethod,
        ?string $paymentDate = null,
        ?string $proofPath = null,
    ): Payment {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Nominal bayar harus lebih dari 0.',
            ]);
        }

        return DB::transaction(function () use (
            $enrollment,
            $stage,
            $amount,
            $paymentMethod,
            $paymentDate,
            $proofPath,
        ) {
            // Kunci enrollment dulu sebelum membaca total pembayaran.
            ClassEnrollment::query()
                ->whereKey($enrollment->getKey())
                ->lockForUpdate()
                ->first(['id']);

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

            // Total pembayaran paket sebelum transaksi baru.
            $alreadyPaid = $this->totalPaid($enrollment);
            $remaining = round($totalBill - $alreadyPaid - $amount, 2);

            if ($remaining < 0) {
                $sisaSaatIni = number_format($totalBill - $alreadyPaid, 0, ',', '.');

                throw ValidationException::withMessages([
                    'amount_paid' => "Jumlah bayar melebihi sisa tagihan (Rp{$sisaSaatIni}).",
                ]);
            }

            if ($remaining > 0) {
                if ($enrollment->payment_scheme === 'Lunas') {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Enrollment ini memakai skema Lunas, nominal bayar harus melunasi seluruh tagihan.',
                    ]);
                }

                if (! $this->enrollmentAllowsInstallment($enrollment)) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Program/paket ini wajib dibayar lunas, tidak bisa dicicil.',
                    ]);
                }
            }

            return Payment::create([
                'enrollment_id' => $enrollment->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'payment_type' => Payment::TYPE_PACKAGE,
                'payment_stage' => $stage,
                'total_bill' => $totalBill,
                'amount_paid' => $amount,
                'remaining_bill' => $remaining,
                'payment_method' => $paymentMethod,
                'payment_date' => $date->toDateString(),
                'payment_proof_path' => $proofPath,
                'status' => $remaining <= 0 ? 'Paid' : 'Partial',
            ]);
        });
    }

    /**
     * Total tagihan enrollment = harga paket + biaya pendaftaran
     * + biaya aktivitas - diskon.
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

    /**
     * Lama kelas (bulan), hanya informasi.
     * Tidak dipakai untuk mengalikan harga.
     */
    public function monthsFor(ClassEnrollment $e): int
    {
        $package = $e->programPackage ?? $e->privatePackage;

        return $this->monthsForPackage($package, ! $e->programPackage);
    }

    /**
     * Total biaya les dari enrollment.
     * Paket reguler dengan skema Termin menggunakan totalFor('Termin').
     * Selain itu, gunakan harga paket apa adanya.
     */
    public function packageTotal(ClassEnrollment $e): ?float
    {
        $isPrivate = ! $e->programPackage;
        $package = $e->programPackage ?? $e->privatePackage;

        if (
            ! $isPrivate
            && $e->payment_scheme === 'Termin'
            && $package?->installment_price
        ) {
            return $package->totalFor('Termin');
        }

        return $this->packageTotalFor($package, $isPrivate);
    }

    /**
     * Lama kelas (bulan) dari paket.
     * Fallback 3 bulan untuk program dan 1 bulan untuk private.
     */
    public function monthsForPackage(mixed $package, bool $isPrivate): int
    {
        return (int) ($package?->duration_months ?: ($isPrivate ? 1 : 3));
    }

    /**
     * Total biaya paket sesuai harga yang tersimpan.
     * Parameter $isPrivate dipertahankan untuk kompatibilitas.
     */
    public function packageTotalFor(mixed $package, bool $isPrivate): ?float
    {
        if (! $package || $package->price === null) {
            return null;
        }

        return (float) $package->price;
    }

    public function allowsInstallment(
        bool $isPrivate,
        ?string $programName,
        ?string $levelName
    ): bool {
        if ($isPrivate) {
            return false;
        }

        if ($programName === 'Daily Activity') {
            return true;
        }

        if (! str_starts_with((string) $levelName, 'HSK ')) {
            return false;
        }

        $level = (int) trim(substr($levelName, 4));

        return $level >= 1 && $level <= 3;
    }

    public function enrollmentAllowsInstallment(ClassEnrollment $e): bool
    {
        return $this->allowsInstallment(
            (bool) $e->private_package_id,
            $e->programPackage?->program?->program_name,
            $this->levelNameFor($e),
        );
    }

    /**
     * Nama level menurut PAKET enrollment, bukan level siswa saat ini, supaya
     * tidak ikut berubah ketika siswa dinaikkan level. Paket tanpa level
     * (Daily Activity, private) jatuh ke level siswa.
     */
    public function levelNameFor(ClassEnrollment $e): ?string
    {
        return $e->programPackage?->level?->level_name
            ?? $e->student?->currentLevel?->level_name;
    }

    /**
     * Total pembayaran khusus paket.
     * Pembayaran biaya tambahan tidak dihitung ke tagihan paket.
     */
    public function totalPaid(ClassEnrollment $enrollment): float
    {
        return (float) $enrollment->packagePayments()->sum('amount_paid');
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

    /**
     * Membuat nomor invoice dengan format yang sama untuk semua jenis pembayaran.
     * Format: INV-YYYYMMDD-XXXXXX
     */
    public function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Payment::where('invoice_number', $number)->exists());

        return $number;
    }
}