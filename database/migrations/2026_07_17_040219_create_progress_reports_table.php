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
        Schema::create('progress_reports', function (Blueprint $table) {

            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('class_id')
                ->constrained('classes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('template_progress_report_id')
                ->constrained('template_progress_reports')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('report_date');

            $table->unsignedTinyInteger('communication');

            $table->unsignedTinyInteger('confidence');

            $table->unsignedTinyInteger('listening');

            $table->unsignedTinyInteger('reading');

            $table->unsignedTinyInteger('writing');

            $table->unsignedTinyInteger('behavior');

            $table->text('homework')->nullable();

            $table->text('teacher_notes')->nullable();

            $table->string('pdf_file')->nullable();

            $table->enum('status',[
                'draft',
                'submitted',
                'approved'
            ])->default('draft');

            $table->timestamps();

            $table->softDeletes();

            $table->index('student_id');
            $table->index('teacher_id');
            $table->index('class_id');
            $table->index('report_date');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_reports');
    }
};