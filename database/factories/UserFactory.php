<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'level_id' => Level::where('nama_level', 'Student')->value('id_level'),
            'username' => fake()->unique()->userName(),
            'password' => Hash::make('password'),
            'status' => 'Active',
        ];
    }

    public function owner(): static
    {
        return $this->state([
            'level_id' => Level::where('nama_level','Owner')->value('id_level'),
        ]);
    }

    public function admin(): static
    {
        return $this->state([
            'level_id' => Level::where('nama_level','Admin')->value('id_level'),
        ]);
    }

    public function curriculum(): static
    {
        return $this->state([
            'level_id' => Level::where('nama_level','Curriculum')->value('id_level'),
        ]);
    }

    public function teacher(): static
    {
        return $this->state([
            'level_id' => Level::where('nama_level','Teacher')->value('id_level'),
        ]);
    }

    public function student(): static
    {
        return $this->state([
            'level_id' => Level::where('nama_level','Student')->value('id_level'),
        ]);
    }
}