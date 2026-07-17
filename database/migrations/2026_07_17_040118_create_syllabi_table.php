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
        Schema::create('syllabi', function (Blueprint $table) {

            $table->id();

            $table->foreignId('program_id')
                ->constrained('programs')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('curriculum_id')
                ->constrained('curriculums')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('title');

            $table->string('level',20);

            $table->text('description')->nullable();

            $table->enum('status',[
                'draft',
                'published',
                'archived'
            ])->default('draft');

            $table->timestamps();

            $table->softDeletes();

            $table->index('program_id');
            $table->index('curriculum_id');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syllabi');
    }
};