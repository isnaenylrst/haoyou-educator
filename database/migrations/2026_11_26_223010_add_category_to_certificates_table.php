<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // hsk12 | hsk36 | reg_rw | reg_ls
            $table->string('category', 20)->nullable()->after('course_name');
        });

        // Kelas Reguler (Membaca+Menulis) tidak punya nilai listening, dan
        // (Mendengar+Berbicara) tidak punya nilai reading, jadi semua komponen harus nullable.
        // Sesuaikan tipe decimal(5,1) dengan migration asli Anda bila berbeda.
        Schema::table('certificates', function (Blueprint $table) {
            $table->decimal('listening_score', 5, 1)->nullable()->change();
            $table->decimal('reading_score', 5, 1)->nullable()->change();
            $table->decimal('writing_score', 5, 1)->nullable()->change();
            $table->decimal('speaking_score', 5, 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};