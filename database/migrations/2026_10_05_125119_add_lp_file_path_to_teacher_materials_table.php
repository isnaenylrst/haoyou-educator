<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_materials', 'lp_file_path')) {
                $table->string('lp_file_path')->nullable()->after('material_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_materials', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_materials', 'lp_file_path')) {
                $table->dropColumn('lp_file_path');
            }
        });
    }
};