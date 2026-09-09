<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {

            $table->id();

            $table->foreignId('level_id')
                ->constrained('program_levels')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedTinyInteger('meeting_number');

            $table->string('title');
            $table->text('syllabus');
            $table->string('material_file_path');

            $table->timestamps();

            $table->unique(['level_id', 'meeting_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};