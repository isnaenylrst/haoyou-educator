<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->decimal('listening_score', 5, 1)->nullable();
            $table->decimal('reading_score', 5, 1)->nullable();
            $table->decimal('speaking_score', 5, 1)->nullable();
            $table->decimal('writing_score', 5, 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['listening_score', 'reading_score', 'speaking_score', 'writing_score']);
        });
    }
};