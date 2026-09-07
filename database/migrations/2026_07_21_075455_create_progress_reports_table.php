<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_reports', function (Blueprint $table) {

            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

            $table->foreignId('enrollment_id')
                ->constrained('class_enrollments')
                ->cascadeOnDelete();

            $table->string('report_type');

            $table->string('report_period');

            $table->string('file_path');

            $table->enum('status', [
                'Draft',
                'Submitted'
            ])->default('Draft');            

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_reports');
    }
};