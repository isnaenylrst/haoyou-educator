<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('progress_reports', 'reviewed_by')) {
                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->after('uploaded_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('progress_reports', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            if (Schema::hasColumn('progress_reports', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }
            if (Schema::hasColumn('progress_reports', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
        });
    }
};