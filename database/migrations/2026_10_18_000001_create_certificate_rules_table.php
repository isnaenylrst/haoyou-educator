<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aturan per level HSK: komponen yang dinilai + batas lulus.
        // Diatur dari database supaya tim kurikulum bisa mengubah tanpa menyentuh kode.
        Schema::create('certificate_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level_number')->unique(); // 1, 2, 3, ...
            $table->json('components');                            // ["listening","reading"]
            $table->unsignedSmallInteger('passing_score');         // 120
            $table->unsignedSmallInteger('max_total');             // 200
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_rules');
    }
};
