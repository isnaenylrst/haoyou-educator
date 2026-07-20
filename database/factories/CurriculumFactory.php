<?php

namespace Database\Factories;

use App\Models\Curriculum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Curriculum>
 */
class CurriculumFactory extends Factory
{
    protected $model = Curriculum::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [

            // Sesuaikan dengan id user yang sudah ada
            'user_id' => 1,

            'nama' => fake()->randomElement([
                'Kepala Kurikulum',
            ]),

            'status' => fake()->boolean(90),

        ];
    }
}