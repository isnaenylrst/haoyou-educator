<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Format nomor invoice:
 *
 *  Paket reguler : INV-20261113-ISNDA-DP | -T1 | -T2 | -T3 | -LS
 *  Paket private : INV-20261113-ISNPRV-DP | -LS
 *  Home Visit    : INV-20261113-ISNHV
 *  Pengganti     : INV-20261113-ISNPP-FXG   (FXG = kategori kelas siswa)
 *  HV + Pengganti: INV-20261113-ISNPHV-FXG
 */
class InvoiceNumberService
{
    /** Nomor untuk satu Payment yang akan dibuat (dipanggil dari event creating). */
    public function forPayment(Payment $payment): string
    {
        $payment->loadMissing([
            'enrollment.student.currentLevel.category',
            'enrollment.programPackage.program',
        ]);

        $enrollment = $payment->enrollment;
        $date       = $payment->payment_date ?: today();

        if ($payment->isExtra()) {
            return $this->forExtra($enrollment, [$payment->payment_type], $date);
        }

        return $this->forPackage($enrollment, (string) $payment->payment_stage, $date);
    }

    public function forPackage(?ClassEnrollment $enrollment, string $stage, $date = null): string
    {
        $program = $enrollment?->private_package_id
            ? 'PRV'
            : $this->abbreviate($enrollment?->programPackage?->program?->program_name);

        $stageCode = config('invoice.stages.' . $stage)
            ?? Str::upper(preg_replace('/\s+/', '', $stage));

        return $this->unique(
            $this->prefix($date) . $this->initials($enrollment) . $program . '-' . $stageCode
        );
    }

    /**
     * @param  string[]  $types  Payment::TYPE_HOME_VISIT dan/atau Payment::TYPE_REPLACEMENT
     */
    public function forExtra(?ClassEnrollment $enrollment, array $types, $date = null): string
    {
        $enrollment?->loadMissing('student.currentLevel.category');

        $hasHome        = in_array(Payment::TYPE_HOME_VISIT, $types, true);
        $hasReplacement = in_array(Payment::TYPE_REPLACEMENT, $types, true);

        $code = match (true) {
            $hasHome && $hasReplacement => 'PHV',
            $hasReplacement             => 'PP',
            default                     => 'HV',
        };

        $number = $this->prefix($date) . $this->initials($enrollment) . $code;

        // Kategori hanya ikut untuk invoice yang memuat pertemuan pengganti
        if ($hasReplacement) {
            $category = $enrollment?->student?->currentLevel?->category?->category_name;

            if (filled($category)) {
                $number .= '-' . $this->abbreviate($category);
            }
        }

        return $this->unique($number);
    }

    /* ---------------------------------------------------------- */

    private function prefix($date): string
    {
        return 'INV-' . Carbon::parse($date ?: today())->format('Ymd') . '-';
    }

    /**
     * Huruf awal tiap kata nama siswa: "Isnaeny Tri Larassati" -> ITL.
     * Nama satu kata memakai 3 huruf pertama supaya kodenya tidak terlalu pendek.
     */
    private function initials(?ClassEnrollment $enrollment): string
    {
        $name  = Str::ascii((string) $enrollment?->student?->name);
        $words = preg_split('/[^A-Za-z]+/', $name, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === []) {
            return 'XXX';
        }

        if (count($words) === 1) {
            return Str::upper(substr($words[0], 0, 3));
        }

        $max = (int) config('invoice.max_initials', 4);

        return Str::upper(implode('', array_map(
            fn ($word) => $word[0],
            array_slice($words, 0, $max)
        )));
    }

    public function abbreviate(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return 'XX';
        }

        $mapped = config('invoice.abbreviations.' . Str::lower($name));

        if ($mapped) {
            return $mapped;
        }

        $words = preg_split('/\s+/', Str::ascii($name), -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) > 1) {
            return Str::upper(substr(implode('', array_map(fn ($w) => $w[0], $words)), 0, 3));
        }

        return Str::upper(substr(preg_replace('/[^A-Za-z0-9]/', '', $words[0]), 0, 3));
    }

    /** Kalau nomor sudah dipakai (mis. dua siswa berinisial sama), tambahkan -2, -3, dst. */
    private function unique(string $number): string
    {
        $candidate = $number;
        $i = 2;

        while (Payment::withTrashed()->where('invoice_number', $candidate)->exists()) {
            $candidate = $number . '-' . $i++;
        }

        return $candidate;
    }
}