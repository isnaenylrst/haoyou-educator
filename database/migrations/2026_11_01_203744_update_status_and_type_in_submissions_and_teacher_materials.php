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
        if (Schema::hasTable('submissions')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->string('type', 100)->change();
                $table->string('status', 50)->default('Diajukan')->change();
            });
        }

        if (Schema::hasTable('teacher_materials')) {
            Schema::table('teacher_materials', function (Blueprint $table) {
                $table->string('status', 50)->default('Diajukan')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible if needed
    }
};
