<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\MaterialVocab;
use Database\Factories\MaterialVocabFactory;
use Illuminate\Database\Seeder;

class MaterialVocabSeeder extends Seeder
{
    public function run(): void
    {
        $materialIds = Material::pluck('id');

        foreach ($materialIds as $materialId) {
            $chosen = collect(MaterialVocabFactory::VOCAB_POOL)
                ->shuffle()
                ->take(5)
                ->values();

            foreach ($chosen as $index => $vocab) {
                MaterialVocab::factory()
                    ->fromPool($vocab)
                    ->create([
                        'material_id' => $materialId,
                        'order_number' => $index + 1,
                    ]);
            }
        }
    }
}