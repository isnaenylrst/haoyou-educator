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
        Schema::create('review_logs', function (Blueprint $table) {

            $table->id();

            $table->foreignId('lesson_plan_id')
                ->constrained('lesson_plans')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('curriculum_id')
                ->constrained('curriculums')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->dateTime('review_date');

            $table->enum('status', [
                'pending',
                'approved',
                'revision',
                'rejected',
            ])->default('pending');

            $table->text('comment')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('lesson_plan_id');
            $table->index('curriculum_id');
            $table->index('review_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_logs');
    }
};