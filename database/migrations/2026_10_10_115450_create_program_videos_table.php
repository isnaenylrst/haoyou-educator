
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_videos', function (Blueprint $table) {

            $table->id();

            $table->enum('type', [
                'culture',
                'replay'
            ]);

            $table->foreignId('level_id')
                ->constrained('program_levels')
                ->cascadeOnDelete();

            // Diisi untuk culture, kosong untuk replay
            $table->unsignedSmallInteger('chapter_number')->nullable();

            $table->string('title');

            $table->string('youtube_id', 20);

            $table->unsignedSmallInteger('duration_minutes')->nullable();

            $table->boolean('is_published')->default(true);

            $table->timestamps();

            // Index pencarian video berdasarkan level, tipe, dan chapter
            $table->index([
                'level_id',
                'type',
                'chapter_number'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_videos');
    }
};
