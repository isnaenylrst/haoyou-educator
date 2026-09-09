<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_packages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // nullable: diisi hanya untuk program yang harganya bergantung
            // kategori/level spesifik (HSK) — null untuk Daily Activity
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('program_categories')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('level_id')
                ->nullable()
                ->constrained('program_levels')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('package_name');

            $table->integer('duration_minutes');
            $table->integer('total_meetings');
            $table->integer('min_students');
            $table->integer('max_students');
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['program_id', 'package_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_packages');
    }
};