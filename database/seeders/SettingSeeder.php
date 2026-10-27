<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'registration_fee' => 300000,
            'activity_fee'     => 300000,
        ];

        foreach ($defaults as $key => $value) {
            // firstOrCreate: kalau key sudah ada (sudah diubah admin), tidak ditimpa
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}