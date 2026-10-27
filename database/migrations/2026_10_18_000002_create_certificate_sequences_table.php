<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penghitung nomor urut sertifikat per tahun & level (HY/HSK1/2026/0001).
        Schema::create('certificate_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('level_number');
            $table->unsignedInteger('last_number')->default(0);
            $table->unique(['year', 'level_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_sequences');
    }
};
