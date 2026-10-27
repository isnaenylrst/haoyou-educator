<?php

$levels = function (string $name, int $to, array $letters): array {
    $out = [];
    for ($n = 1; $n <= $to; $n++) {
        foreach ($letters as $l) {
            $out[] = "$name $n$l";
        }
    }
    return $out;
};

return [
    'courses' => [
        'Regular' => [
            'Feixiang'  => $levels('Feixiang', 3, ['A', 'B', 'C', 'D']),
            'Hudie'     => $levels('Hudie', 3, ['A', 'B', 'C', 'D']),
            'Jianer'    => $levels('Jianer', 6, ['A', 'B']),
            'Maochoung' => $levels('Maochoung', 6, ['A', 'B']),
        ],
        'Private' => [],
        'Bisnis'  => [],
        'HSK'     => ['HSK 1', 'HSK 2', 'HSK 3', 'HSK 4', 'HSK 5', 'HSK 6'],
    ],

    'focus' => ['Membaca & Menulis', 'Mendengar & Berbicara'],

    'attendance_status' => [
        'Present'    => 'Hadir',
        'Absent'     => 'Alpa',
        'Sick'       => 'Sakit',
        'Permission' => 'Izin',
    ],
];