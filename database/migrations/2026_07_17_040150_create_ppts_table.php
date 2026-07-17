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
        Schema::create('ppts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('lesson_plan_id')
                ->constrained('lesson_plans')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title');

            $table->string('file_path');

            $table->enum('status',[
                'draft',
                'submitted',
                'approved',
                'revision',
                'rejected'
            ])->default('draft');

            $table->timestamps();

            $table->softDeletes();

            $table->index('lesson_plan_id');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppts');
    }
};