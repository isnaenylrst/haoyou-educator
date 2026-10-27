<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kelas pengganti tidak punya kelas induk (class_id null), jadi perlu penanda
     * siswa lewat pendaftarannya (enrollment).
     */
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('class_enrollment_id')
                ->nullable()
                ->after('class_id')
                ->constrained('class_enrollments')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_enrollment_id');
        });
    }
};