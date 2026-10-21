<?php

namespace Database\Seeders;

use App\Models\CertificateRule;
use Illuminate\Database\Seeder;

class CertificateRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // HSK 1 & 2: Listening + Reading, lulus 120 dari 200 (60%)
            ['level_number' => 1, 'components' => ['listening', 'reading'], 'passing_score' => 120, 'max_total' => 200],
            ['level_number' => 2, 'components' => ['listening', 'reading'], 'passing_score' => 120, 'max_total' => 200],
            // HSK 3: Listening + Reading + Writing, lulus 180 dari 300 (60%)
            ['level_number' => 3, 'components' => ['listening', 'reading', 'writing'], 'passing_score' => 180, 'max_total' => 300],
            // HSK 4 ke atas: tambahkan di sini setelah standarnya dari kurikulum jelas.
        ];

        foreach ($rules as $rule) {
            CertificateRule::updateOrCreate(['level_number' => $rule['level_number']], $rule);
        }
    }
}
