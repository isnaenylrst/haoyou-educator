<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ClassSession;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExtraFeeService
{
    public const FEE_REPLACEMENT = 175000;

    public const FEE_HOME_VISIT = 50000;

    public function __construct(
        private PaymentInstallmentService $installments,
    ) {}

    /**
     * Mengambil tarif berdasarkan tipe biaya tambahan.
     */
    public function amountFor(string $type): int
    {
        return match ($type) {
            Payment::TYPE_REPLACEMENT => self::FEE_REPLACEMENT,
            Payment::TYPE_HOME_VISIT => self::FEE_HOME_VISIT,
            default => throw ValidationException::withMessages([
                'type' => 'Tipe biaya tambahan tidak valid.',
            ]),
        };
    }

    /**
     * Mencatat pembayaran biaya tambahan ke tabel payments.
     *
     * $pay berisi:
     * - payment_date
     * - payment_method
     * - payment_proof_path (opsional)
     * - amount (opsional, default mengikuti tarif)
     */
    public function record(
        ClassEnrollment $enrollment,
        string $type,
        ClassSession $session,
        array $pay,
    ): Payment {
        // Biaya tambahan hanya untuk enrollment yang masih aktif.
        if ($enrollment->status !== 'Active') {
            throw ValidationException::withMessages([
                'enrollment_id' => 'Biaya tambahan hanya dapat dicatat untuk enrollment berstatus Active.',
            ]);
        }

        // Validasi tipe biaya.
        if (! in_array($type, Payment::EXTRA_TYPES, true)) {
            throw ValidationException::withMessages([
                'type' => 'Tipe biaya tambahan tidak valid.',
            ]);
        }

        // Pastikan tipe memiliki tarif yang ditentukan.
        $defaultAmount = $this->amountFor($type);

        // Validasi tanggal pembayaran.
        if (empty($pay['payment_date'])) {
            throw ValidationException::withMessages([
                'payment_date' => 'Tanggal pembayaran wajib diisi.',
            ]);
        }

        try {
            $paymentDate = Carbon::parse($pay['payment_date'])->startOfDay();
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'payment_date' => 'Tanggal pembayaran tidak valid.',
            ]);
        }

        if ($paymentDate->isAfter(today())) {
            throw ValidationException::withMessages([
                'payment_date' => 'Tanggal bayar tidak boleh di masa depan.',
            ]);
        }

        // Tanggal pembayaran tidak boleh sebelum tanggal enrollment.
        if ($enrollment->enrollment_date) {
            $enrollmentDate = Carbon::parse(
                $enrollment->enrollment_date
            )->startOfDay();

            if ($paymentDate->lt($enrollmentDate)) {
                throw ValidationException::withMessages([
                    'payment_date' =>
                        'Tanggal bayar tidak boleh sebelum tanggal enrollment.',
                ]);
            }
        }

        // Validasi nominal pembayaran.
        $amount = $pay['amount'] ?? $defaultAmount;

        if (
            ! is_numeric($amount)
            || (float) $amount <= 0
        ) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran harus lebih dari 0.',
            ]);
        }

        $amount = (float) $amount;

        // Validasi metode pembayaran.
        if (empty($pay['payment_method'])) {
            throw ValidationException::withMessages([
                'payment_method' => 'Metode pembayaran wajib diisi.',
            ]);
        }

        return DB::transaction(function () use (
            $enrollment,
            $type,
            $session,
            $pay,
            $paymentDate,
            $amount,
        ) {

            $lockedSession = ClassSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Validasi hubungan sesi dengan enrollment.
            if ($lockedSession->class_enrollment_id !== null) {
                // Sesi khusus enrollment, misalnya sesi pengganti.
                if ((int) $lockedSession->class_enrollment_id !== (int) $enrollment->id) {
                    throw ValidationException::withMessages([
                        'session' => 'Sesi ini bukan milik enrollment yang dipilih.',
                    ]);
                }
            } else {
                // Sesi kelas bersama harus memiliki kelas yang sama
                // dengan kelas pada enrollment.
                if (
                    $lockedSession->class_id === null
                    || $enrollment->class_id === null
                    || (int) $lockedSession->class_id !== (int) $enrollment->class_id
                ) {
                    throw ValidationException::withMessages([
                        'session' => 'Sesi ini tidak sesuai dengan kelas enrollment.',
                    ]);
                }
            }

            // Sesi yang dibatalkan tidak boleh menerima pembayaran.
            if ($lockedSession->status === 'Cancelled') {
                throw ValidationException::withMessages([
                    'session' => 'Sesi yang dibatalkan tidak dapat menerima pembayaran.',
                ]);
            }

            // Biaya Kelas Pengganti hanya untuk sesi Replacement.
            if (
                $type === Payment::TYPE_REPLACEMENT
                && ! $lockedSession->isReplacement()
            ) {
                throw ValidationException::withMessages([
                    'session' => 'Biaya Kelas Pengganti hanya berlaku untuk sesi pengganti.',
                ]);
            }

            // Biaya Home Visit hanya untuk sesi berlokasi Home Visit.
            if (
                $type === Payment::TYPE_HOME_VISIT
                && ! $lockedSession->isHomeVisit()
            ) {
                throw ValidationException::withMessages([
                    'session' => 'Biaya Home Visit hanya berlaku untuk sesi Home Visit.',
                ]);
            }

            // Periksa pembayaran ganda untuk sesi dan tipe biaya yang sama.
            $alreadyPaid = Payment::query()
                ->where('class_session_id', $lockedSession->id)
                ->where('payment_type', $type)
                ->exists();

            if ($alreadyPaid) {
                throw ValidationException::withMessages([
                    'session' => 'Sesi ini sudah memiliki pembayaran dengan tipe biaya tersebut.',
                ]);
            }
            $paymentStage = match ($type) {
                Payment::TYPE_REPLACEMENT => 'Kelas Pengganti',
                Payment::TYPE_HOME_VISIT => 'Home Visit',
                default => throw ValidationException::withMessages([
                    'type' => 'Tipe biaya tambahan tidak valid.',
                ]),
            };

            return Payment::create([
                'enrollment_id' => $enrollment->id,
                'class_session_id' => $lockedSession->id,
                'invoice_number' => $this->installments
                    ->generateInvoiceNumber(),
                'payment_type' => $type,
                'payment_stage' => $paymentStage,
                'total_bill' => $amount,
                'amount_paid' => $amount,
                'remaining_bill' => 0,
                'status' => 'Paid',
                'payment_method' => $pay['payment_method'],
                'payment_date' => $paymentDate->toDateString(),
                'payment_proof_path' => $pay['payment_proof_path'] ?? null,
            ]);
        });
    }
}