<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('progress_reports', 'header_data')) {
                $table->json('header_data')->nullable();
            }
            if (!Schema::hasColumn('progress_reports', 'document_edited_at')) {
                $table->timestamp('document_edited_at')->nullable();
            }
            if (!Schema::hasColumn('progress_reports', 'document_edited_by')) {
                $table->unsignedBigInteger('document_edited_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            foreach (['header_data', 'document_edited_at', 'document_edited_by'] as $col) {
                if (Schema::hasColumn('progress_reports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};