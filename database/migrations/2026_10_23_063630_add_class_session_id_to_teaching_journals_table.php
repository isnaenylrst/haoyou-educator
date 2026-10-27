<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penambahan classsession
     */
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->foreignId('class_session_id')
                ->nullable()
                ->after('class_schedule_id')
                ->constrained('class_sessions')
                ->nullOnDelete();

            // Satu pertemuan hanya punya satu jurnal (NULL pada jurnal lama tidak bentrok)
            $table->unique('class_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            // Urutan penting di MySQL: lepas foreign key dulu, baru unique, baru kolomnya
            $table->dropForeign(['class_session_id']);
            $table->dropUnique(['class_session_id']);
            $table->dropColumn('class_session_id');
        });
    }
};