<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ClassSession;
use App\Models\SessionFee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Biaya tambahan per pertemuan (di luar tagihan paket).
 */
class SessionFeeService
{
    public const FEE_REPLACEMENT = 175000; // kelas pengganti tambahan, per pertemuan
    public const FEE_HOME_VISIT  = 50000;  // guru mengajar ke rumah murid, per pertemuan

    /**
     * Samakan baris biaya dengan kondisi pertemuan saat ini (aman dipanggil berulang):
     * - pertemuan tambahan (kind Replacement)             -> Replacement
     * - lokasi Home Visit                                -> Home Visit
     * - pertemuan batal                                  -> tidak ada biaya
     * Biaya yang sudah dibayar tidak pernah dihapus.
     */
    public function syncFor(ClassSession $session): void
    {
        $wanted = [];

        if ($session->status !== 'Cancelled') {
            if ($session->isReplacement()) {
                $wanted['Replacement'] = self::FEE_REPLACEMENT;
            }
            if ($session->isHomeVisit()) {
                $wanted['Home Visit'] = self::FEE_HOME_VISIT;
            }
        }

        $enrollmentId = $session->class_enrollment_id ?? $this->soleEnrollmentOf($session);

        foreach ($wanted as $type => $amount) {
            SessionFee::firstOrCreate(
                ['class_session_id' => $session->id, 'fee_type' => $type],
                ['class_enrollment_id' => $enrollmentId, 'amount' => $amount, 'status' => 'Unpaid'],
            );
        }

        SessionFee::where('class_session_id', $session->id)
            ->where('status', 'Unpaid')
            ->whereNotIn('fee_type', array_keys($wanted))
            ->delete();
    }

    /** Tandai satu biaya sudah dibayar. */
    public function markPaid(SessionFee $fee, array $data): SessionFee
    {
        if ($fee->isPaid()) {
            throw ValidationException::withMessages(['fee' => 'Biaya ini sudah ditandai lunas.']);
        }

        $paidAt = Carbon::parse($data['paid_at']);
        if ($paidAt->isFuture()) {
            throw ValidationException::withMessages(['paid_at' => 'Tanggal bayar tidak boleh di masa depan.']);
        }

        return DB::transaction(function () use ($fee, $data, $paidAt) {
            $fee->update([
                'status'             => 'Paid',
                'paid_at'            => $paidAt->toDateString(),
                'payment_method'     => $data['payment_method'],
                'payment_proof_path' => $data['payment_proof_path'] ?? null,
                'invoice_number'     => $this->nextInvoiceNumber($paidAt),
            ]);

            return $fee;
        });
    }

    /**
     * Nomor invoice biaya tambahan: INV-EX-YYYYMMDD-0001 (urut per tanggal bayar).
     * Prefix berbeda dari invoice pembayaran paket supaya tidak tertukar.
     */
    private function nextInvoiceNumber(Carbon $date): string
    {
        $prefix = 'INV-EX-' . $date->format('Ymd') . '-';

        $last = SessionFee::where('invoice_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /** Untuk kelas yang hanya punya satu siswa aktif (mis. private), penanggung biaya jelas. */
    private function soleEnrollmentOf(ClassSession $session): ?int
    {
        if (! $session->class_id) {
            return null;
        }

        $ids = ClassEnrollment::where('class_id', $session->class_id)
            ->where('status', 'Active')
            ->pluck('id');

        return $ids->count() === 1 ? $ids->first() : null;
    }
}