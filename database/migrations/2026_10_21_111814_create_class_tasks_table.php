<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')
                ->constrained('classes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();

            $table->enum('status', ['Open', 'Done'])->default('Open');
            $table->timestamp('done_at')->nullable();
            $table->text('teacher_note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_tasks');
    }
};