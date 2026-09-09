<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeacherSeeder extends Seeder
{
    /**
     * Data diambil dari employees.sql, difilter yang position = 'Teacher'.
     */
    public function run(): void
    {
        // Ambil id_level untuk 'Teacher' dari tabel levels (hasil LevelSeeder).
        $teacherLevelId = DB::table('levels')
            ->where('nama_level', 'Teacher')
            ->value('id_level');

        $teachers = [
            [
                'name'      => 'Teresa Liaunardo Tju',
                'phone'     => '08816173529',
                'address'   => 'Sigura Gura 4',
                'join_date' => '2026-05-05',
            ],
            [
                'name'      => 'Vanessa Tan',
                'phone'     => '087816276207',
                'address'   => 'Jl. Kerto Asri Dalam, No 122A',
                'join_date' => '2026-08-11',
            ],
            [
                'name'      => 'Natalia Repiuli Dertina Sitorus',
                'phone'     => '082195354707',
                'address'   => 'Sulawesi Tenggara',
                'join_date' => '2026-05-04',
            ],
            [
                'name'      => 'Kezia Sophi Adventa Ratta',
                'phone'     => '081938798913',
                'address'   => 'Jalan Taman Borobudur Kencana 1 No 26',
                'join_date' => '2026-07-24',
            ],
            [
                'name'      => 'Marsya Amelia',
                'phone'     => '088805477868',
                'address'   => 'Jl. MT. Haryono, Gg. 6c No. 858, Dinoyo',
                'join_date' => '2026-07-02',
            ],
            [
                'name'      => 'Nathania Jovita',
                'phone'     => '081275530745',
                'address'   => 'Jl. Kerto Asri No. 122A',
                'join_date' => '2026-06-12',
            ],
            [
                'name'      => 'Fitri Maulidah',
                'phone'     => '081946728321',
                'address'   => 'RT 22 RW 04 Dusun Madatan Desa Panggul Kecamatan Panggul Kabupaten Trenggalek',
                'join_date' => '2026-02-02',
            ],
            [
                'name'      => 'Dwi Ayu Wulandari',
                'phone'     => '085857418637',
                'address'   => 'Jl. Kopda Sukoco RT 17 RW 01 Wonokerto Kec Bantur',
                'join_date' => '2026-07-08',
            ],
        ];

        foreach ($teachers as $data) {
            $username = Str::slug($data['name'], '_');

            $user = User::firstOrCreate(
                ['username' => $username],
                [
                    'level_id' => $teacherLevelId,
                    'password' => Hash::make('password'), // ganti sesuai kebutuhan
                    'status'   => 'Active',
                ]
            );

            Teacher::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name'            => $data['name'],
                    'phone'           => $data['phone'],
                    'address'         => $data['address'],
                    'specialist'      => null, // tidak tersedia di employees.sql
                    'join_date'       => $data['join_date'],
                    'training_status' => null, // tidak tersedia di employees.sql
                    'status'          => 'Active',
                ]
            );
        }
    }
}