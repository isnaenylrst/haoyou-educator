<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda kelas private yang gurunya mengajar ke rumah murid.
     * Pertemuan yang dibangkitkan untuk kelas ini berlokasi "Home Visit" dan
     * otomatis kena biaya Rp50.000 per pertemuan (lihat SessionFeeService).
     */
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->boolean('is_home_visit')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('is_home_visit');
        });
    }
};