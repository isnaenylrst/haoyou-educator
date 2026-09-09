<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_package_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('level_id')
                ->constrained('program_levels')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('class_name');

            $table->enum('delivery_mode', ['Offline', 'Online']);

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->unsignedTinyInteger('capacity');

            $table->enum('status', ['Open', 'Running', 'Completed', 'Closed'])->default('Open');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};