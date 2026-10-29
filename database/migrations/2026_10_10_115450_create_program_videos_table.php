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
    Schema::create('program_videos', function (Blueprint $table) {
        $table->id();
        $table->enum('type', ['culture', 'replay']);
        $table->enum('audience', ['hsk', 'kids'])->nullable();   // untuk video budaya
        $table->foreignId('level_id')->nullable()
              ->constrained('program_levels')->cascadeOnDelete(); // untuk video replay
        $table->string('title');
        $table->string('video_path');
        $table->unsignedSmallInteger('duration_minutes')->nullable();
        $table->boolean('is_published')->default(true);
        $table->timestamps();

        $table->unique(['type', 'audience']);   // 1 video budaya per audience
        $table->unique(['type', 'level_id']);   // 1 replay per level
    });
}

public function down(): void
{
    Schema::dropIfExists('program_videos');
}
};