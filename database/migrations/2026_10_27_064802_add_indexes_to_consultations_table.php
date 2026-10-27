<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->index(['status', 'scheduled_at'], 'consultations_status_scheduled_idx');
            $table->index(['teacher_id', 'status'], 'consultations_teacher_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropIndex('consultations_status_scheduled_idx');
            $table->dropIndex('consultations_teacher_status_idx');
        });
    }
};