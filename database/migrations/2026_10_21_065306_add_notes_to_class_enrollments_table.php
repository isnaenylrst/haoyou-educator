<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_enrollments', function (Blueprint $table) {
            // Catatan per enrollment, mis. siswa masuk di pertengahan kelas / dapat potongan
            $table->text('notes')->nullable()->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('class_enrollments', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};