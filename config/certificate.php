<?php

return [
    // Nama program (kolom program_name) yang boleh menerbitkan sertifikat
    'program_name' => env('CERT_PROGRAM_NAME', 'HSK'),

    'issue_place'  => 'Malang',
    'signer_name'  => 'Joy Limmunandar',
    'signer_title' => 'Owner HaoYou Educator',

   'logo_path'      => public_path('assets/img/certificate/logo.png'),
   'signature_path' => public_path('assets/img/certificate/ttd.png'),
];
    