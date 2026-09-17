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
                'amount' => "Jumlah bayar melebihi sisa tagihan (Rp{$sisaSaatIni}).",
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

    public function totalBillFor(ClassEnrollment $enrollment): ?float
    {
        $price = $enrollment->programPackage->price
            ?? $enrollment->privatePackage->price
            ?? null;

        return $price !== null ? (float) $price : null;
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