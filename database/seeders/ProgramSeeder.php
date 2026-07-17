<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        Program::insert([

            [
                'nama_program'=>'Daily Activity',

                'tipe_kelas'=>'Regular',

                'fokus'=>'Conversation',

                'durasi'=>'90',

                'jumlah_pertemuan'=>16,

                'min_siswa'=>4,

                'max_siswa'=>8,

                'aktif'=>true,
            ],

            [
                'nama_program'=>'HSK 1',

                'tipe_kelas'=>'Regular',

                'fokus'=>'HSK',

                'durasi'=>'90',

                'jumlah_pertemuan'=>16,

                'min_siswa'=>2,

                'max_siswa'=>6,

                'aktif'=>true,
            ],

            [
                'nama_program'=>'Private',

                'tipe_kelas'=>'Private',

                'fokus'=>'Custom',

                'durasi'=>'90',

                'jumlah_pertemuan'=>16,

                'min_siswa'=>1,

                'max_siswa'=>1,

                'aktif'=>true,
            ],

        ]);
    }
}