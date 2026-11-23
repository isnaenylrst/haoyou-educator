<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_videos', function (Blueprint $table) {
            $table->id();

            // NULL = video untuk semua siswa aktif
            $table->foreignId('program_level_id')
                ->nullable()
                ->constrained('program_levels')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // Sumber video: file atau YouTube
            $table->string('source_type', 10)->default('file');
            $table->string('youtube_url')->nullable();
            $table->string('youtube_id', 20)->nullable();

            // Jenis video: culture atau replay
            $table->string('kind', 20)->default('culture');

            // File video
            $table->string('file_path')->nullable();
            $table->string('mime_type', 50)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->boolean('is_published')->default(true);

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_videos');
    }
};