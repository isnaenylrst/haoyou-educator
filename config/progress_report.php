<?php

/*
|--------------------------------------------------------------------------
| Pengaturan dokumen Progress Report (mengikuti contoh PDF Haoyou)
|--------------------------------------------------------------------------
*/
return [
    'default_title' => 'TRIMESTER ACADEMIC PROGRESS REPORT',
    'city'          => 'Malang',
    'director_title' => 'Direktur Haoyou Educator',

    // Penandatangan tetap (sama dengan sertifikat). Ubah ejaan: .env REPORT_SIGNER_NAME="..."
    'signer_name'   => env('REPORT_SIGNER_NAME', config('certificate.signer_name', 'Joy Limmunandar')),

    // true = guru TIDAK bisa membuat report bila Kurikulum belum membuat template Learning Materials program kelasnya
    'require_template' => (bool) env('REPORT_REQUIRE_TEMPLATE', true),

    'levels' => ['Sufficient', 'Proficient', 'Excellent'],

    'legend' => [
        'Sufficient' => [
            'description' => 'The student has met the minimum proficiency standard in Mandarin but still requires reinforcement in certain areas.',
            'criteria' => [
                'Pronunciation and Intonation' => 'The student can pronounce some basic words and phrases but frequently makes errors in pronunciation or intonation.',
                'Listening Comprehension'      => 'The student can understand slow and simple basic conversations but often requires repetition or additional clarification.',
                'Speaking Skills'              => 'The student can answer simple questions but with limited sentences and frequent hesitation.',
            ],
        ],
        'Proficient' => [
            'description' => 'The student has demonstrated proficient understanding and skills in Mandarin, with few errors.',
            'criteria' => [
                'Pronunciation and Intonation' => 'Most words are pronounced correctly, with intonation that is fairly accurate and close to that of a native speaker.',
                'Listening Comprehension'      => 'The student can understand conversations at a moderate speed and grasp the main points without needing much repetition.',
                'Speaking Skills'              => 'The student is able to speak in more complex sentences and express simple opinions in Mandarin.',
            ],
        ],
        'Excellent' => [
            'description' => 'The student demonstrates excellent proficiency in Mandarin and is able to use it confidently in various situations.',
            'criteria' => [
                'Pronunciation and Intonation' => 'Almost all words are pronounced clearly, with intonation that closely resembles that of a native speaker.',
                'Listening Comprehension'      => 'Able to understand conversations at a normal speed, including complex discussions or those with many details.',
                'Speaking Skills'              => 'Fluent in speaking, capable of engaging in discussions, expressing opinions, and telling stories with well-structured sentences.',
            ],
        ],
    ],

    'closing_note' => "*Don't be afraid to start the things you love! Also, you have more potential than you think ! . 加油！辛苦啦，别放弃！。老师很高兴认识你！",
];