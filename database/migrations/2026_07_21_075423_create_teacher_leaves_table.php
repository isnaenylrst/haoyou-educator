<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_leaves', function (Blueprint $table) {

            $table->id();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

            $table->foreignId('class_schedule_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('leave_type',[
                'Sick',
                'Permission'
            ]);

            $table->text('reason');

            $table->string('supporting_document')->nullable();

            $table->foreignId('replacement_teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->nullOnDelete();

            $table->enum('status',[
                'Pending',
                'Approved',
                'Rejected'
            ])->default('Pending');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_leaves');
    }
};