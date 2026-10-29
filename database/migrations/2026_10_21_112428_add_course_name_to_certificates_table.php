<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Nama kursus/level yang tercetak di sertifikat (bisa diedit guru)
            if (!Schema::hasColumn('certificates', 'course_name')) {
                $table->string('course_name', 150)->nullable()->after('certificate_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'course_name')) {
                $table->dropColumn('course_name');
            }
        });
    }
};