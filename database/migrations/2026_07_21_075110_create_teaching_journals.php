<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_journals', function (Blueprint $table) {
            $table->id();

            // Guru yang mengajar
            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Kelas yang diajar
            $table->foreignId('class_id')
                ->constrained('classes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Jadwal mengajar
            $table->foreignId('class_schedule_id')
                ->constrained('class_schedules')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Pertemuan aktual
            $table->foreignId('class_session_id')
                ->nullable()
                ->constrained('class_sessions')
                ->nullOnDelete();

            $table->date('session_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            // Materi pembelajaran
            $table->foreignId('material_id')
                ->nullable()
                ->constrained('materials')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('course_type', 20)->nullable();
            $table->string('course_level', 50)->nullable();
            $table->json('focus_materials')->nullable();
            $table->text('material_taught')->nullable();

            // Guru pengganti
            $table->boolean('is_substitute')->default(false);

            $table->foreignId('substitute_teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('teacher_leave_id')
                ->nullable()
                ->constrained('teacher_leaves')
                ->nullOnDelete();

            // Informasi pertemuan
            $table->boolean('is_last_meeting')->default(false);
            $table->unsignedSmallInteger('meeting_number')->nullable();

            // Status kelas
            $table->enum('class_status', [
                'Conducted',
                'Cancelled',
                'Rescheduled',
            ]);

            // Aktivitas pembelajaran
            $table->text('learning_activities');

            // Kendala
            $table->text('problems')->nullable();

            // Solusi
            $table->text('solutions')->nullable();

            // Hasil pembelajaran
            $table->text('results')->nullable();

            // Nilai pertemuan
            $table->unsignedTinyInteger('session_score')->nullable();

            // Catatan tambahan
            $table->text('notes')->nullable();

            // Nama siswa dan pengisi jurnal
            $table->text('student_names')->nullable();
            $table->unsignedBigInteger('filled_by')->nullable();

            // Status review jurnal
            $table->enum('status', [
                'Pending',
                'Reviewed',
                'Revision',
            ])->default('Pending');

            $table->boolean('is_late')->default(false);

            $table->text('review_note')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            // Satu jadwal hanya memiliki satu jurnal pada tanggal yang sama
            $table->unique(
                ['class_schedule_id', 'session_date'],
                'journal_schedule_date_unique'
            );

            // Satu pertemuan hanya memiliki satu jurnal
            $table->unique('class_session_id');

            // Index status review
            $table->index('status', 'teaching_journals_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_journals');
    }
};