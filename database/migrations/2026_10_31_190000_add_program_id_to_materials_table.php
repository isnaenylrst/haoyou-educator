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
        Schema::table('materials', function (Blueprint $table) {
            // 1. Tambah program_id nullable + foreign key ke program_categories + index
            if (!Schema::hasColumn('materials', 'program_id')) {
                $table->foreignId('program_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('program_categories')
                    ->nullOnDelete();
            }

            // 2. Buat level_id menjadi nullable (level/kelas opsional)
            $table->unsignedBigInteger('level_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'program_id')) {
                $table->dropConstrainedForeignId('program_id');
            }
        });
    }
};

