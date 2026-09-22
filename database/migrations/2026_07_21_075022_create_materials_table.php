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
        Schema::create('materials', function (Blueprint $table) {

            $table->id();

            $table->foreignId('level_id')
                ->constrained('program_levels')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // User yang upload materi
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedTinyInteger('meeting_number');

            // Judul materi
            $table->string('title');

            // Silabus
            $table->text('syllabus')->nullable();

            // Lokasi file materi
            $table->string('material_file_path');

            $table->timestamps();

            $table->unique(['level_id', 'meeting_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};