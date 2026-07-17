<?php

namespace Database\Seeders;

use App\Models\FollowupTemplate;
use Illuminate\Database\Seeder;

class FollowupTemplateSeeder extends Seeder
{
    public function run(): void
    {
        FollowupTemplate::insert([

            [

                'nama_template'=>'Follow Up Trial',

                'kategori'=>'Trial',

                'isi_template'=>'Halo Kak, bagaimana hasil trial kemarin? 😊',

                'status'=>true

            ],

            [

                'nama_template'=>'Reminder Pembayaran',

                'kategori'=>'Payment',

                'isi_template'=>'Halo Kak, kami mengingatkan bahwa pembayaran akan jatuh tempo.',

                'status'=>true

            ],

            [

                'nama_template'=>'Follow Up Cold Lead',

                'kategori'=>'Lead',

                'isi_template'=>'Halo Kak, apakah masih berminat mengikuti kelas Mandarin di Haoyou? 😊',

                'status'=>true

            ]

        ]);
    }
}