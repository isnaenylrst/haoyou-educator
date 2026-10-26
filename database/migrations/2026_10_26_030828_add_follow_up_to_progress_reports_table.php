<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
        $table->enum('follow_up_status', ['Belum', 'Menunggu Jawaban', 'Lanjut', 'Tidak Lanjut'])->default('Belum');
        $table->timestamp('followed_up_at')->nullable();
        $table->text('follow_up_note')->nullable();
    });
}

public function down(): void
{
    Schema::table('progress_reports', function (Blueprint $table) {
        $table->dropColumn(['follow_up_status', 'followed_up_at', 'follow_up_note']);
    });
}
};