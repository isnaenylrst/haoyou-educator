<?php

namespace App\Services;

class TerbilangService
{
    private static array $bilangan = [
        '',
        'satu',
        'dua',
        'tiga',
        'empat',
        'lima',
        'enam',
        'tujuh',
        'delapan',
        'sembilan',
        'sepuluh',
        'sebelas',
    ];

    /**
     * Konversi angka nominal ke kalimat terbilang Bahasa Indonesia.
     * Contoh: 1200000 -> "Satu juta dua ratus ribu rupiah"
     */
    public static function make(float|int|string $nominal, bool $withCurrency = true): string
    {
        $angka = abs((float) $nominal);

        if ($angka < 1) {
            $hasil = 'nol';
        } else {
            $hasil = trim(self::convert((int) floor($angka)));
        }

        // Bersihkan multiple spaces
        $hasil = preg_replace('/\s+/', ' ', $hasil);
        $kalimat = ucfirst($hasil);

        if ($withCurrency) {
            $kalimat .= ' rupiah';
        }

        return $kalimat;
    }

    private static function convert(int $angka): string
    {
        if ($angka < 12) {
            return self::$bilangan[$angka];
        }

        if ($angka < 20) {
            return self::convert($angka - 10) . ' belas';
        }

        if ($angka < 100) {
            $sisa = $angka % 10;
            return self::convert((int) ($angka / 10)) . ' puluh ' . ($sisa ? self::convert($sisa) : '');
        }

        if ($angka < 200) {
            $sisa = $angka - 100;
            return 'seratus ' . ($sisa ? self::convert($sisa) : '');
        }

        if ($angka < 1000) {
            $sisa = $angka % 100;
            return self::convert((int) ($angka / 100)) . ' ratus ' . ($sisa ? self::convert($sisa) : '');
        }

        if ($angka < 2000) {
            $sisa = $angka - 1000;
            return 'seribu ' . ($sisa ? self::convert($sisa) : '');
        }

        if ($angka < 1000000) {
            $sisa = $angka % 1000;
            return self::convert((int) ($angka / 1000)) . ' ribu ' . ($sisa ? self::convert($sisa) : '');
        }

        if ($angka < 1000000000) {
            $sisa = $angka % 1000000;
            return self::convert((int) ($angka / 1000000)) . ' juta ' . ($sisa ? self::convert($sisa) : '');
        }

        if ($angka < 1000000000000) {
            $sisa = $angka % 1000000000;
            return self::convert((int) ($angka / 1000000000)) . ' miliar ' . ($sisa ? self::convert($sisa) : '');
        }

        if ($angka < 1000000000000000) {
            $sisa = $angka % 1000000000000;
            return self::convert((int) ($angka / 1000000000000)) . ' triliun ' . ($sisa ? self::convert($sisa) : '');
        }

        return (string) $angka;
    }
}
