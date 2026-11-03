<?php

namespace App\Support;

class GrammarData
{
    /** Daftar grammar: no, hanzi, arti, pinyin, fungsi, pola, contoh[[hanzi, indo]] */
    public static function list(): array
    {
        return [
            [
                'no' => 1, 'hanzi' => '得不得了', 'arti' => 'sangat sekali',
                'pinyin' => 'de bùdéliǎo', 'fungsi' => 'Menyatakan tingkat ekstrem',
                'pola' => 'Kata sifat + 得不得了',
                'contoh' => [
                    ['今天忙得不得了。', 'Hari ini sibuk sekali.'],
                    ['这家店贵得不得了。', 'Toko ini mahal sekali.'],
                    ['他高兴得不得了。', 'Dia senang sekali.'],
                ],
            ],
            [
                'no' => 2, 'hanzi' => '得很', 'arti' => 'sangat',
                'pinyin' => 'de hěn', 'fungsi' => 'Menyatakan tingkat sangat',
                'pola' => 'Kata sifat + 得很',
                'contoh' => [
                    ['这个问题难得很。', 'Masalah ini sangat sulit.'],
                    ['天气冷得很。', 'Cuacanya sangat dingin.'],
                    ['他紧张得很。', 'Dia sangat gugup.'],
                ],
            ],
            [
                'no' => 3, 'hanzi' => '……了一点儿', 'arti' => 'terlalu sedikit',
                'pinyin' => 'le yìdiǎnr', 'fungsi' => 'Menyatakan “agak terlalu”',
                'pola' => 'Kata sifat + 了 + 一点儿',
                'contoh' => [
                    ['这个房间小了一点儿。', 'Kamar ini agak terlalu kecil.'],
                    ['今天早了一点儿。', 'Hari ini agak terlalu pagi.'],
                    ['价格高了一点儿。', 'Harganya agak terlalu mahal.'],
                ],
            ],
            [
                'no' => 4, 'hanzi' => '有的是', 'arti' => 'banyak sekali',
                'pinyin' => 'yǒu de shì', 'fungsi' => 'Menyatakan jumlah sangat banyak',
                'pola' => 'Subjek + 有的是 + kata benda',
                'contoh' => [
                    ['这里有的是机会。', 'Di sini banyak sekali kesempatan.'],
                    ['他有的是时间。', 'Dia punya banyak waktu.'],
                    ['公司有的是资源。', 'Perusahaan punya banyak sumber daya.'],
                ],
            ],
        ];
    }

    /**
     * Grammar traps.
     * kolom  : kata pembanding di header tabel
     * baris  : [hanzi, [ok1, ok2, ...]]  (true = cocok, false = tidak)
     */
    public static function traps(): array
    {
        return [
            [
                'a' => '得很', 'b' => '得不得了',
                'rows' => [
                    ['得很',     'Sangat (netral, deskriptif)', '他高兴得很。', 'Dia sangat senang.'],
                    ['得不得了', 'Sangat sekali / berlebihan, emosi kuat', '今天热得不得了。', 'Hari ini panas sekali.'],
                ],
                'cols'  => ['紧张~', '热~', '安静~', '累~'],
                'table' => [
                    '得很'     => [true, true, true, true],
                    '得不得了' => [false, true, false, true],
                ],
            ],
            [
                'a' => '简直', 'b' => '格外',
                'rows' => [
                    ['简直', 'Benar-benar / keterlaluan, reaksi emosional', '这简直不可能。', 'Ini benar-benar mustahil.'],
                    ['格外', 'Terutama / lebih dari biasanya', '今天他格外认真。', 'Hari ini dia sangat serius.'],
                ],
                'cols'  => ['~不可能', '~认真', '~气死了'],
                'table' => [
                    '简直' => [true, false, true],
                    '格外' => [false, true, false],
                ],
            ],
            [
                'a' => '反正', 'b' => '毕竟',
                'rows' => [
                    ['反正', 'Bagaimanapun juga, tidak peduli hasil', '反正我已经说过了。', 'Pokoknya aku sudah bilang.'],
                    ['毕竟', 'Bagaimanapun juga, alasan/fakta penting', '他毕竟是老师。', 'Bagaimanapun juga dia guru.'],
                ],
                'cols'  => ['~我不去', '~已经决定了', '~是第一次', '~他是老师'],
                'table' => [
                    '反正' => [true, true, false, false],
                    '毕竟' => [false, false, true, true],
                ],
            ],
            [
                'a' => '常常', 'b' => '经常',
                'rows' => [
                    ['常常', 'Sering, kebiasaan personal', '我常常迟到。', 'Aku sering terlambat.'],
                    ['经常', 'Sering, umum & objektif', '这里经常下雨。', 'Di sini sering hujan.'],
                ],
                'cols'  => ['我~迟到', '我~生气', '~堵车'],
                'table' => [
                    '常常' => [true, true, false],
                    '经常' => [true, true, true],
                ],
            ],
        ];
    }

    /**
     * Soal test. Status tiap pilihan: benar | kurang | salah
     */
    public static function questions(): array
    {
        return [
            ['no' => 1, 'soal' => 'Hari ini panas sekali.', 'options' => [
                ['今天热得很', 'kurang'], ['今天热得不得了', 'benar'], ['今天热了一点儿', 'salah'],
            ]],
            ['no' => 2, 'soal' => 'Masalah ini sangat sulit.', 'options' => [
                ['这个问题难得不得了', 'kurang'], ['这个问题难了一点儿', 'salah'], ['这个问题难得很', 'benar'],
            ]],
            ['no' => 3, 'soal' => 'Kamar ini agak terlalu kecil.', 'options' => [
                ['这个房间小得很', 'kurang'], ['这个房间小了一点儿', 'benar'], ['这个房间有的是小', 'salah'],
            ]],
            ['no' => 4, 'soal' => 'Dia punya banyak waktu.', 'options' => [
                ['他有的是时间', 'benar'], ['他总是时间', 'salah'], ['他有很多时间', 'kurang'],
            ]],
            ['no' => 5, 'soal' => 'Segampang ini pun kamu tidak tahu.', 'options' => [
                ['这么简单你还不知道', 'kurang'], ['这么简单你毕竟不知道', 'salah'], ['这么简单你都不知道', 'benar'],
            ]],
        ];
    }
}