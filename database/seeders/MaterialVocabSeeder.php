<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\MaterialVocab;
use Illuminate\Database\Seeder;

class MaterialVocabSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap materi mendapat 5 kosakata contoh, order_number 1-5.
     */
    public function run(): void
    {
        $materialIds = Material::pluck('id');

        foreach ($materialIds as $materialId) {
            for ($order = 1; $order <= 5; $order++) {
                MaterialVocab::factory()->create([
                    'material_id' => $materialId,
                    'order_number' => $order,
                ]);
            }
        }
    }
}
