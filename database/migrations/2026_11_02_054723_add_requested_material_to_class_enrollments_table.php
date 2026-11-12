<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materi yang diminta siswa private. Disimpan per enrollment (bukan per kelas) karena
 * kelas private Exclusive bisa diikuti beberapa siswa dengan permintaan materi berbeda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_enrollments', function (Blueprint $table) {
            $table->text('requested_material')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('class_enrollments', function (Blueprint $table) {
            $table->dropColumn('requested_material');
        });
    }
};