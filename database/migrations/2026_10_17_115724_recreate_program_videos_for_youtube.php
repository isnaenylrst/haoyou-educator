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
    Schema::dropIfExists('program_videos');

    Schema::create('program_videos', function (Blueprint $table) {
        $table->id();
        $table->enum('type', ['culture', 'replay']);
        $table->foreignId('level_id')->constrained('program_levels')->cascadeOnDelete();
        $table->unsignedSmallInteger('chapter_number')->nullable(); // diisi untuk culture, kosong untuk replay
        $table->string('title');
        $table->string('youtube_id', 20);
        $table->unsignedSmallInteger('duration_minutes')->nullable();
        $table->boolean('is_published')->default(true);
        $table->timestamps();

        $table->index(['level_id', 'type', 'chapter_number']);
    });
}

public function down(): void
{
    Schema::dropIfExists('program_videos');
}
};