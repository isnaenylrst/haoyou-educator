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
        Schema::create('consultation_schedules', function (Blueprint $table) {

            $table->id();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('curriculum_id')
                ->constrained('curriculums')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->dateTime('consultation_date');

            $table->string('topic');

            $table->text('result')->nullable();

            $table->enum('status',[
                'scheduled',
                'completed',
                'cancelled'
            ])->default('scheduled');

            $table->timestamps();

            $table->softDeletes();

            $table->index('teacher_id');
            $table->index('curriculum_id');
            $table->index('consultation_date');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_schedules');
    }
};