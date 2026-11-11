<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_tasks', function (Blueprint $table) {
            // Path file lampiran (nullable — tugas tidak wajib punya lampiran)
            $table->string('attachment_path')->nullable()->after('teacher_note');
            // Nama asli file untuk ditampilkan ke pengguna
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('class_tasks', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
    }
};
