<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            if (!Schema::hasColumn('consultations', 'teacher_material_id')) {
                $table->foreignId('teacher_material_id')
                    ->nullable()
                    ->after('teacher_id')
                    ->constrained('teacher_materials')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            if (Schema::hasColumn('consultations', 'teacher_material_id')) {
                $table->dropConstrainedForeignId('teacher_material_id');
            }
        });
    }
};