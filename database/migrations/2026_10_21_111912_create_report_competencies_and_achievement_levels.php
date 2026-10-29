<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kompetensi yang dibuat Kurikulum. program_level_id NULL = berlaku untuk semua level.
        Schema::create('report_competencies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_level_id')
                ->nullable()
                ->constrained('program_levels')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // Deskripsi baku per Achievement Level (guru tidak mengetik deskripsi).
        Schema::create('achievement_levels', function (Blueprint $table) {
            $table->id();
            $table->string('level', 50)->unique();
            $table->text('description');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('achievement_levels')->insert([
            [
                'level'       => 'Excellent',
                'description' => 'Demonstrates outstanding mastery of the material and applies it accurately and confidently without assistance.',
                'sort_order'  => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'level'       => 'Proficient',
                'description' => 'Demonstrates a solid understanding of the material and applies it correctly with minimal assistance.',
                'sort_order'  => 2,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'level'       => 'Good',
                'description' => 'Demonstrates an adequate understanding of the material and applies it correctly with some guidance; more practice is recommended.',
                'sort_order'  => 3,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'level'       => 'Needs Improvement',
                'description' => 'Has not yet mastered the material and requires additional practice and guidance to reach the expected level.',
                'sort_order'  => 4,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_levels');
        Schema::dropIfExists('report_competencies');
    }
};