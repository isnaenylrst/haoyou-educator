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
        if (Schema::hasTable('teacher_materials')) {
            Schema::table('teacher_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('teacher_materials', 'task_id')) {
                    $table->foreignId('task_id')
                        ->nullable()
                        ->after('class_id')
                        ->constrained('class_tasks')
                        ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teacher_materials')) {
            Schema::table('teacher_materials', function (Blueprint $table) {
                if (Schema::hasColumn('teacher_materials', 'task_id')) {
                    $table->dropForeign(['task_id']);
                    $table->dropColumn('task_id');
                }
            });
        }
    }
};
