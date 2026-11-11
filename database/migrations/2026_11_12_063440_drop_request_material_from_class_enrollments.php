<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('class_enrollments', 'request_material')) {
            return;
        }

        // Pengaman: bila ada isi, salin ke requested_material sebelum kolom lama dihapus
        if (Schema::hasColumn('class_enrollments', 'requested_material')) {
            DB::statement("
                UPDATE class_enrollments
                SET requested_material = request_material
                WHERE (requested_material IS NULL OR requested_material = '')
                  AND request_material IS NOT NULL
                  AND request_material <> ''
            ");
        }

        Schema::table('class_enrollments', function (Blueprint $table) {
            $table->dropColumn('request_material');
        });
    }

    public function down(): void
    {
        // Sengaja kosong: kolom request_material memang dihapus karena duplikat.
    }
};