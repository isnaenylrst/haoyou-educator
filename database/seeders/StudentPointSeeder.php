<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

/**
 * Mengisi students.points dari
 * "Point Murid Haoyou (Responses) - AKUMULASI POINT MURID HAOYOU.pdf".
 *
 * Jalankan SETELAH StudentSeeder.
 * Aman dijalankan ulang: poin di-SET (bukan ditambah), jadi hasilnya selalu sama.
 *
 * Nama di PDF singkat / urutannya beda, jadi dipetakan manual ke nama siswa
 * di StudentSeeder. Format: 'Nama siswa di database' => total poin.
 */
class StudentPointSeeder extends Seeder
{
    private function points(): array
    {
        return [
            'Abel'                              => 25,
            'Abiyoga Permana C'                 => 30,
            'Adhyaksa M. F.'                    => 105,
            'Adinda Salsabilla Dhea Rahmanda'   => 125,
            // di PDF muncul 2x: "ADITAMA AIRLANGGA" (65) + "AIRLANGGA ADITAMA" (10)
            'Airlangga Aditama'                 => 75,
            'Adzhar Syafiq'                     => 10,
            'Afif Malik Gunawan'                => 35,
            'Agnes Wibowo'                      => 165,
            'Aisyah Azzahra'                    => 10,
            'Claire Alexa Lee'                  => 205,
            'Alifson Aquirera'                  => 95,
            'Almira Rusyda Haflani'             => 30,
            'Retno Ambar Arum'                  => 30,
            'Andrew Farrel Tjia'                => 90,
            'Angellica'                         => 30,
            'Anisa Joy'                         => 50,
            'Aurelia Keyko Putri W'             => 5,
            'Axel Alvindra'                     => 40,
            'Ayline Queenie Margaretha'         => 5,
            'Bara Sang Timur'                   => 135,
            'Bernadheta Michiko Hiltraud Muijs' => 175,
            'Betrand Sebastian Tjia'            => 60,
            'Bryan'                             => 80,
            'Cherin Dharani'                    => 10,
            'Cheryl Caroline R. S'              => 10,
            'Cheryl Hannesa Nathania'           => 15,
            'Christian Farrel'                  => 190,
            'Clement Richard Santoso'           => 115,
            'Danisha Nur Rashaqa Alya'          => 90,
            'Danu Athaya'                       => 375,
            'Darren'                            => 75,
            'Diego Gracio'                      => 170,
            'Dimas Arya Satria Priyandia'       => 30,
            'Dinda Kamila'                      => 95,
            'Elizabeth Renata'                  => 75,
            'Elya Tri'                          => 175,
            'Evan Everest Bek'                  => 15,
            'Farah'                             => 60,
            'Farhan M. K'                       => 10,
            'Ferdyansyah'                       => 10,
            'Firdaus Wahyu S'                   => 10,
            'Gabriella Yovita'                  => 15,
            'Gabriella Timothy Junifertya A'    => 10,
            'Ghania Putri Nazira R'             => 110,
            'Gregoria Alexa'                    => 35,
            'Gregorius Raphael'                 => 175,
            'Hana Almahyra'                     => 135,
            'Hana Fitriyah'                     => 120,
            'Hana Rosita'                       => 75,
            'Ibrahim'                           => 205,
            'Iola Marsha'                       => 95,
            'James Linata'                      => 175,
            'M. Jennifer Callysta P'            => 5,
            'Jessen Jordan'                     => 70,
            'Jesslyn Dibyo Vannopabbo'          => 190,
            'Jonathan Imanuel'                  => 60,
            'Josephine Antonia'                 => 5,
            'Jovita Vania'                      => 50,
            'Justin Kelvin'                     => 5,
            'Kaira'                             => 15,
            'Kayla Ellen'                       => 110,
            'Keith Ethan Setiawan'              => 65,
            'Ken Christian'                     => 60,
            'Keyzia Liemmisela'                 => 10,
            'Khanza Sakhi N.K'                  => 70,
            'Khusnul Maarif'                    => 165,
            'Kim Wijaya'                        => 170,
            'Lily Middleton Wijaya'             => 5,
            'Lionel Richie Lorenzo'             => 80,
            'Lissa Dixie Kania'                 => 105,
            'Lulus'                             => 60,
            'M. Bagas'                          => 85,
            'Marcia Angelia Josephine'          => 70,
            'Josephine Marcia Leonita'          => 20,
            'Maria Elena Yap'                   => 60,
            'Michael'                           => 25, // Michael HSK 1 (yang pertama dibuat)
            'Nathanael Kenneth Brian Akulah'    => 110,
            'Nathaniel Sugiarto'                => 50,
            'Nisrina Nabila Mustika Sakti'      => 120,
            'Nova Nadia Faradiba'               => 25,
            'Nursyafiqah Hazimah D'             => 25,
            'Patrick F. Santoso'                => 60,
            'Phoenix Kai Atlantis'              => 230,
            'Rohmawan Mujadah W'                => 20,
            'Roshaura Dewi Ryani Zahra'         => 5,
            // di PDF muncul 2x: "RUNNA PRIVATE" (10) + "RUNNA THEDA" (10)
            'Runna Theda M'                     => 20,
            'Safri Maulana'                     => 150,
            'Saina Kasya Mairani'               => 5,
            'Samantha Clarissa'                 => 165,
            'Sandrica'                          => 80,
            'Sarah'                             => 20,
            'Sebastian Benjamin Santoso'        => 170,
            'Sekar Ayu'                         => 10,
            'Shintya Fuadiah'                   => 60,
            'Syafiq Ibrahim'                    => 210,
            'Syarif Hidayatullah'               => 170,
            'Taruna Patria'                     => 20,
            'Theophilus Imanuel'                => 125,
            'Theresa Avila'                     => 55,
            'Tiffanny Christy Kurniawan'        => 235,
            'Utsman Hidayat'                    => 205,
            'Valdy Daniel'                      => 45,
            'Vania Salsa N. H'                  => 5,
            'Yeni Patricia'                     => 15,
            'Yobel Wu'                          => 20,
            'Yofi Nanda P'                      => 70,
            'Yulia Ananda'                      => 75,
            'Zahra Aulia Fitri'                 => 30,
            'Zoey Alisha'                       => 440,
        ];
    }

    /**
     * Ada di PDF poin tapi TIDAK ada di daftar siswa (PDF DATA_INTERNAL_CLIENT).
     * Tidak di-seed. Kalau ternyata siswa yang sama dengan ejaan berbeda,
     * pindahkan ke points() di atas dengan nama siswa yang benar.
     */
    private const TIDAK_DITEMUKAN = [
        'ABDUL AZIZ' => 55, 'AKMAL BAGYA' => 30, 'ARINI ELSA' => 40, 'ASYAM PRIVATE' => 20,
        'AZEL JULIEZA' => 20, 'AZKIA UMMU' => 15, 'BAYU TRI' => 50, 'BEADLEY JIANER' => 5,
        'BRADLEY JIANER' => 25, 'BRIAN PRIVATE' => 35, 'CHARLOTTE MANUELA' => 40,
        'FERNANDO AMSAL' => 60, 'JASON ARRON' => 35, 'LUTHFI MARIATUS' => 55,
        'NABILA KHAIRAH' => 10, 'THALITA SEPTRIANE' => 20, 'THEODORE SEBASTIAN' => 25,
        'UMI SARY' => 10, 'YOHANES PRIVATE' => 20,
    ];

    public function run(): void
    {
        $updated  = 0;
        $notFound = [];

        foreach ($this->points() as $name => $points) {
            // orderBy id: kalau ada nama kembar (mis. "Michael"), ambil yang pertama dibuat
            $student = Student::where('name', $name)->orderBy('id')->first();

            if (! $student) {
                $notFound[] = $name;
                continue;
            }

            $student->update(['points' => $points]);
            $updated++;
        }

        $this->command->info("Poin diisi untuk {$updated} siswa.");

        if ($notFound) {
            $this->command->warn('Siswa tidak ada di database (cek StudentSeeder): ' . implode(', ', $notFound));
        }

        $this->command->warn(count(self::TIDAK_DITEMUKAN) . ' nama di PDF poin tidak ada di daftar siswa dan dilewati: '
            . implode(', ', array_keys(self::TIDAK_DITEMUKAN)));
    }
}