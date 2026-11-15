<?php

if (!function_exists('format_rupiah')) {
    /**
     * Format nominal mata uang rupiah.
     *
     * Contoh:
     * - 25000 -> "Rp 25.000"
     * - 0 -> "Rp 0"
     * - 1500000 -> "Rp 1.500.000"
     * - -25000 -> "-Rp 25.000"
     *
     * @param float|int|numeric-string|null $value
     * @return string
     */
    function format_rupiah($value): string
    {
        if ($value === null || $value === '') {
            return 'Rp 0';
        }

        // Jika string memiliki tanda koma/titik ribuan, bersihkan
        if (is_string($value)) {
            $cleaned = trim($value);
            // Jika format string seperti "10.000" yang sengaja dimasukkan sebagai ribuan
            // tapi bukan float murni:
            if (is_numeric($cleaned)) {
                $num = (float) $cleaned;
            } else {
                // Hapus non numerik kecuali minus dan dot/comma
                $cleaned = preg_replace('/[^\d\.\,\-]/', '', $cleaned);
                $num = (float) str_replace(',', '.', $cleaned);
            }
        } else {
            $num = (float) $value;
        }

        $isNegative = $num < 0;
        $abs = abs($num);

        $formatted = number_format($abs, 0, ',', '.');

        return ($isNegative ? '-Rp ' : 'Rp ') . $formatted;
    }
}

