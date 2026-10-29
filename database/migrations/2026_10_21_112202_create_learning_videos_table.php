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

            $table->string('file_path');
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