<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class NotFutureDate implements ValidationRule
{
    /**
     * Reusable untuk field tanggal yang tidak boleh diisi masa depan —
     * session_date (guru boleh telat input jurnal, tapi tidak boleh
     * input jadwal yang belum terjadi), payment_date, dll.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Carbon::parse($value)->isFuture()) {
            $fail('Kolom :attribute tidak boleh diisi tanggal di masa depan.');
        }
    }
}