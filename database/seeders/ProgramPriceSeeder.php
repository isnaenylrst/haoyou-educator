<?php

namespace Database\Seeders;

use App\Models\ProgramPrice;
use Illuminate\Database\Seeder;

class ProgramPriceSeeder extends Seeder
{
    public function run(): void
    {
        ProgramPrice::insert([

            [

                'program_id'=>1,

                'nama_paket'=>'Daily Activity',

                'nominal'=>1800000,

                'payment_scheme'=>'Termin',

                'max_termin'=>3,

                'effective_from'=>'2026-01-01',

                'status'=>true,

            ],

            [

                'program_id'=>2,

                'nama_paket'=>'HSK 1',

                'nominal'=>2500000,

                'payment_scheme'=>'Termin',

                'max_termin'=>3,

                'effective_from'=>'2026-01-01',

                'status'=>true,

            ],

            [

                'program_id'=>3,

                'nama_paket'=>'Private',

                'nominal'=>3500000,

                'payment_scheme'=>'Full',

                'max_termin'=>1,

                'effective_from'=>'2026-01-01',

                'status'=>true,

            ],

        ]);
    }
}