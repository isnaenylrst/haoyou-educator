<?php

namespace App\Jobs;

use App\Models\CandidateStudent;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ExpireRegistrationFees
{
    use Dispatchable;

    /**
     * Tandai fee pendaftaran calon siswa sebagai Expired kalau:
     * - registration_fee_status masih 'Active'
     * - registration_fee_paid_at sudah lewat 30 hari
     * - belum ada konversi ke `students` (belum lanjut DP program)
     *
     * TIDAK pakai ShouldQueue — jalan langsung sinkron saat scheduler
     * trigger, tidak butuh queue worker.
     */
    public function handle(): void
    {
        $expiredCount = CandidateStudent::eligibleForFeeExpiry()
            ->update(['registration_fee_status' => 'Expired']);

        if ($expiredCount > 0) {
            Log::info("ExpireRegistrationFees: {$expiredCount} fee pendaftaran calon siswa di-expire.");
        }
    }
}