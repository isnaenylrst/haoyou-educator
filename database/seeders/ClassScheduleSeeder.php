<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ClassScheduleSeeder extends Seeder
{
    public const ROOMS = ['Beijing', 'Basement', 'Atas', 'Kaca'];

    // Jam operasional di sheet: 09.30 - 21.00 (dalam menit sejak 00.00)
    public const OPENING_MINUTES = 9 * 60 + 30;
    public const CLOSING_MINUTES = 21 * 60;

    public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    public function run(): void
    {
        //
    }
}