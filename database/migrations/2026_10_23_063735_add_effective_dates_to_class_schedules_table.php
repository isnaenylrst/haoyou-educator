<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();

            $table->index('effective_until');
        });
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropIndex(['effective_until']);
            $table->dropColumn(['effective_from', 'effective_until']);
        });
    }
};