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
        Schema::table('progress_reports', function (Blueprint $table) {
        $table->unsignedSmallInteger('listening_score')->nullable();
        $table->unsignedSmallInteger('reading_score')->nullable();
        $table->unsignedSmallInteger('writing_score')->nullable();
        $table->timestamp('published_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('progress_reports', function (Blueprint $table) {
        $table->dropColumn(['listening_score', 'reading_score', 'writing_score', 'published_at']);
    });
    }
};
