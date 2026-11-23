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

            // Relasi siswa, guru, dan enrollment
            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

            $table->foreignId('enrollment_id')
                ->constrained('class_enrollments')
                ->cascadeOnDelete();

            // Informasi laporan
            $table->string('report_type');
            $table->string('report_period');

            $table->string('overall_achievement_level', 50)->nullable();

            $table->string('file_path')->nullable();

            // Status laporan
            $table->enum('status', [
                'Draft',
                'Submitted',
                'Published',
            ])->default('Draft');

            // Tindak lanjut
            $table->enum('follow_up_status', [
                'Belum',
                'Menunggu Jawaban',
                'Lanjut',
                'Tidak Lanjut',
            ])->default('Belum');

            $table->timestamp('followed_up_at')->nullable();
            $table->text('follow_up_note')->nullable();

            // Review Kurikulum
            $table->text('review_note')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->date('extended_until')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            // Nilai HSK
            $table->unsignedSmallInteger('listening_score')->nullable();
            $table->unsignedSmallInteger('reading_score')->nullable();
            $table->unsignedSmallInteger('writing_score')->nullable();

            $table->timestamp('published_at')->nullable();

            // Data dokumen laporan
            $table->json('header_data')->nullable();
            $table->json('content')->nullable();
            $table->timestamp('document_edited_at')->nullable();
            $table->unsignedBigInteger('document_edited_by')->nullable();

            // Rekap kehadiran
            $table->unsignedSmallInteger('attendance_present')->default(0);
            $table->unsignedSmallInteger('attendance_total')->default(0);

            // User yang mengunggah laporan
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