<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_tasks', function (Blueprint $table) {
            $table->index(['status', 'due_date'], 'class_tasks_status_due_idx');
            $table->index(['teacher_id', 'status'], 'class_tasks_teacher_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('class_tasks', function (Blueprint $table) {
            $table->dropIndex('class_tasks_status_due_idx');
            $table->dropIndex('class_tasks_teacher_status_idx');
        });
    }
};