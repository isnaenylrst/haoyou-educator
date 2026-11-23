<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            // Sertifikat kelas
            $table->foreignId('enrollment_id')
                ->nullable()
                ->unique()
                ->constrained('class_enrollments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->nullable()
                ->constrained('students')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('certificate_number', 50);
            $table->string('course_name', 150)->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('score', 5, 1)->nullable();

            $table->string('file_path')->nullable();

            // Status sertifikat kelas
            $table->string('status', 20)->default('Pending');
            $table->text('review_note')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            // Sertifikat ujian / progress report
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('program_level_id')
                ->nullable()
                ->constrained('program_levels')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('progress_report_id')->nullable();

            $table->uuid('verification_code')->nullable()->unique();

            $table->string('student_name')->nullable();
            $table->string('level_name', 50)->nullable();
            $table->unsignedTinyInteger('level_number')->nullable();

            $table->json('scores')->nullable();

            $table->unsignedSmallInteger('total_score')->nullable();
            $table->unsignedSmallInteger('max_total')->nullable();
            $table->unsignedSmallInteger('passing_score')->nullable();

            $table->enum('result', ['passed', 'not_passed'])->nullable();
            $table->timestamp('issued_at')->nullable();

            // Nilai per komponen ujian
            $table->decimal('listening_score', 5, 1)->nullable();
            $table->decimal('reading_score', 5, 1)->nullable();
            $table->decimal('speaking_score', 5, 1)->nullable();
            $table->decimal('writing_score', 5, 1)->nullable();

            // Satu sertifikat ujian per siswa per level
            $table->unique(['user_id', 'program_level_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};