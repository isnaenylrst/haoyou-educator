<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->text('review_note')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('review_note')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->index('status', 'teaching_journals_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->dropIndex('teaching_journals_status_idx');
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['review_note', 'reviewed_by', 'reviewed_at']);
        });
    }
};