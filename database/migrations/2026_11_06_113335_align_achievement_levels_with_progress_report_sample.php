<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Menyelaraskan deskripsi Achievement Level dengan contoh PDF. Teks yang sudah Anda ubah TIDAK ditimpa. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('achievement_levels')) {
            return;
        }

        $legend = config('progress_report.legend');
        $old = [
            'Excellent'  => 'Demonstrates outstanding mastery of the material and applies it accurately and confidently without assistance.',
            'Proficient' => 'Demonstrates a solid understanding of the material and applies it correctly with minimal assistance.',
        ];

        foreach ($old as $level => $oldText) {
            DB::table('achievement_levels')
                ->where('level', $level)
                ->where('description', $oldText)
                ->update(['description' => $legend[$level]['description'], 'updated_at' => now()]);
        }

        if (!DB::table('achievement_levels')->where('level', 'Sufficient')->exists()) {
            DB::table('achievement_levels')->insert([
                'level'       => 'Sufficient',
                'description' => $legend['Sufficient']['description'],
                'sort_order'  => 3,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};