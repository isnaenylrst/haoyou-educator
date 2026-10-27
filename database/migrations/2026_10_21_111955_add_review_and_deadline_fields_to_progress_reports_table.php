<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            // Alasan Kurikulum saat mengembalikan report ke guru
            if (!Schema::hasColumn('progress_reports', 'review_note')) {
                $table->text('review_note')->nullable();
            }
            // Terisi saat dikembalikan, dikosongkan lagi saat guru kirim ulang
            if (!Schema::hasColumn('progress_reports', 'returned_at')) {
                $table->timestamp('returned_at')->nullable();
            }
            // Waktu guru mengirim ke Kurikulum
            if (!Schema::hasColumn('progress_reports', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
            // Perpanjangan batas H+3 yang diberikan Kurikulum
            if (!Schema::hasColumn('progress_reports', 'extended_until')) {
                $table->date('extended_until')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            foreach (['review_note', 'returned_at', 'submitted_at', 'extended_until'] as $col) {
                if (Schema::hasColumn('progress_reports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};