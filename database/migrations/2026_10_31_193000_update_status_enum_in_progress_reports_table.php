<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Perbarui enum status di progress_reports agar mencakup 'Published' (setelah di-ACC Kurikulum)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE progress_reports MODIFY COLUMN status ENUM('Draft', 'Submitted', 'Published') NOT NULL DEFAULT 'Draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE progress_reports MODIFY COLUMN status ENUM('Draft', 'Submitted') NOT NULL DEFAULT 'Draft'");
        }
    }
};

