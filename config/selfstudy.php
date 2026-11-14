<?php

return [
    'weight_vocab'   => 70,
    'weight_grammar' => 30,
    'passing_score'  => 60,

    // batas minimal => predikat
    'predikat' => [
        90 => 'Sangat Baik',
        75 => 'Baik',
        60 => 'Cukup',
    ],

    // path relatif ke folder public/
    'logo' => 'assets/img/logo.png',
    
    // Penandatangan.
    // user_id : id akun (Kurikulum / Owner) yang sudah mengunggah tanda tangan di Profil. Isi agar gambar TTD otomatis dipakai.
    // signature : alternatif, path gambar TTD relatif ke folder public/ (dipakai kalau user_id kosong).
    'curriculum' => [
        'name'      => 'Ioushi Fitri',
        'title'     => 'Kepala Kurikulum HaoYou Educator',
        'user_id'   => 3,
        'signature' => null,
    ],
    'owner' => [
        'name'      => 'Joy Limmunandar',
        'title'     => 'Owner HaoYou Educator',
        'user_id'   => 1,
        'signature' => null,
    ],
];