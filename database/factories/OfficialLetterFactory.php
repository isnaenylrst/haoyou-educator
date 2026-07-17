<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialLetterFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {

        return [

            'user_id' => User::factory(),

            'title' => fake()->randomElement([
                'Surat Tugas Mengajar',
                'Surat Keterangan Guru',
                'Surat Izin Mengajar',
                'Surat Pengangkatan Guru',
                'Surat Peringatan',
            ]),

            'category' => fake()->randomElement([
                'Surat Tugas',
                'Surat Keterangan',
                'Surat Izin',
                'Surat Pengangkatan',
                'Surat Peringatan',
                'Surat Edaran',
            ]),

            'description' => fake()->paragraph(),

            'file_path' => 'official_letters/' . fake()->uuid() . '.pdf',

            'status' => fake()->randomElement([
                'Draft',
                'Published',
                'Archived',
            ]),

        ];

    }
}