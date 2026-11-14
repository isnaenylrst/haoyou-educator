<?php

/**
 * Singkatan untuk nomor invoice, mis. INV-20261113-ISNDA-DP.
 * Kunci memakai huruf kecil. Nama yang tidak terdaftar disingkat otomatis:
 * beberapa kata -> huruf awal tiap kata (Daily Activity = DA), satu kata -> 3 huruf pertama.
 */
return [
    'abbreviations' => [
        // program
        'daily activity' => 'DA',
        'hsk'            => 'HSK',
        'private'        => 'PRV',

        // kategori program
        'feixiang'       => 'FXG',
        'maochong'       => 'MCG',
    ],

    // kode tahap pembayaran paket
    'stages' => [
        'DP'        => 'DP',
        'Termin 1'  => 'T1',
        'Termin 2'  => 'T2',
        'Termin 3'  => 'T3',
        'Lunas'     => 'LS',
        'Pelunasan' => 'LS',
    ],

    // maksimal jumlah kata nama siswa yang diambil huruf awalnya (Baskara Hendra Samudra = BHS)
    'max_initials' => 4,
];