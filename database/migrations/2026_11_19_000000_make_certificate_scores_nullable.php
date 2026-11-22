<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // speaking tidak dipakai lagi, writing kosong untuk HSK 1-2
            $table->decimal('speaking_score', 5, 1)->nullable()->change();
            $table->decimal('writing_score', 5, 1)->nullable()->change();
            // total sampai 300 (sudah decimal(5,1) di migration awal, ditegaskan lagi)
            $table->decimal('score', 5, 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        // dibiarkan kosong: mengembalikan ke NOT NULL bisa gagal jika sudah ada data null
    }
};