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
        Schema::create('lesson_plans', function (Blueprint $table) {

            $table->id();

            $table->foreignId('class_schedule_id')
                ->constrained('class_schedules')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('learning_material_id')
                ->constrained('learning_materials')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('title');

            $table->text('objective');

            $table->longText('activity');

            $table->text('assessment');

            $table->enum('status',[
                'draft',
                'submitted',
                'approved',
                'revision',
                'rejected'
            ])->default('draft');

            $table->timestamps();

            $table->softDeletes();

            $table->index('teacher_id');
            $table->index('class_schedule_id');
            $table->index('learning_material_id');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};