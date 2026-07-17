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
        Schema::create('teaching_logs', function (Blueprint $table) {

            $table->id();

            $table->foreignId('class_schedule_id')
                ->constrained('class_schedules')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('lesson_plan_id')
                ->constrained('lesson_plans')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->text('summary');

            $table->text('obstacle')->nullable();

            $table->text('follow_up')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('teacher_id');
            $table->index('class_schedule_id');
            $table->index('lesson_plan_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_logs');
    }
};