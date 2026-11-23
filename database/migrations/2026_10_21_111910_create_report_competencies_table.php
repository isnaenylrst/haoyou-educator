<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_competencies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_level_id')
                ->nullable()
                ->constrained('program_levels')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_competencies');
    }
};