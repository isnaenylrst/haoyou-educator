<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template Progress Report untuk kelas PRIVATE.
 *
 * Kelas private tidak punya level_id (memakai private_package_id), jadi template-nya
 * dikaitkan ke Paket Private (Admin -> Program & Level -> Paket Private).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_report_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('progress_report_templates', 'private_package_id')) {
                $table->unsignedBigInteger('private_package_id')->nullable();
                $table->index('private_package_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('progress_report_templates', function (Blueprint $table) {
            if (Schema::hasColumn('progress_report_templates', 'private_package_id')) {
                $table->dropIndex(['private_package_id']);
                $table->dropColumn('private_package_id');
            }
        });
    }
};
