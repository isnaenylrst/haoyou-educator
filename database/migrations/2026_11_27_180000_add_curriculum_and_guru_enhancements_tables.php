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
        // 1. KURIKULUM 2: Tugas Perbaikan Materi guru tidak terikat kelas -> class_id nullable di class_tasks
        if (Schema::hasTable('class_tasks')) {
            Schema::table('class_tasks', function (Blueprint $table) {
                // Buat class_id nullable (additive modification)
                $table->unsignedBigInteger('class_id')->nullable()->change();
            });
        }

        // 2. GURU 1a: Lokasi pada teaching_journals ("Di Haoyou" / "Visit ke rumah")
        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (!Schema::hasColumn('teaching_journals', 'location')) {
                    $table->string('location', 50)->nullable()->after('class_status');
                }
            });
        }

        // 3. GURU 1c & 1d: Achievement level & Description per siswa pada attendances
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('attendances', 'achievement_level')) {
                    $table->string('achievement_level', 50)->nullable()->after('note');
                }
                if (!Schema::hasColumn('attendances', 'description')) {
                    $table->text('description')->nullable()->after('achievement_level');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (Schema::hasColumn('attendances', 'description')) {
                    $table->dropColumn('description');
                }
                if (Schema::hasColumn('attendances', 'achievement_level')) {
                    $table->dropColumn('achievement_level');
                }
            });
        }

        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (Schema::hasColumn('teaching_journals', 'location')) {
                    $table->dropColumn('location');
                }
            });
        }

        if (Schema::hasTable('class_tasks')) {
            Schema::table('class_tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('class_id')->nullable(false)->change();
            });
        }
    }
};
