<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();

            // Kelas induk
            $table->foreignId('class_id')
                ->nullable()
                ->constrained('classes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Enrollment untuk kelas pengganti
            $table->foreignId('class_enrollment_id')
                ->nullable()
                ->constrained('class_enrollments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Pola jadwal mingguan
            $table->foreignId('class_schedule_id')
                ->nullable()
                ->constrained('class_schedules')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->enum('kind', [
                'Regular',
                'Replacement'
            ])->default('Regular');

            $table->unsignedSmallInteger('session_number')->nullable();

            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->string('room')->nullable();

            // Guru terjadwal
            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->enum('location_type', [
                'Studio',
                'Online',
                'Home Visit'
            ])->default('Studio');

            // Link kelas online (Zoom/Meet)
            $table->string('meeting_link', 500)->nullable();

            $table->enum('status', [
                'Scheduled',
                'Completed',
                'Cancelled'
            ])->default('Scheduled');

            $table->date('original_date')->nullable();
            $table->string('change_reason')->nullable();

            $table->timestamps();

            // Nomor pertemuan unik per kelas
            $table->unique(['class_id', 'session_number']);

            // Index pengecekan jadwal
            $table->index(['teacher_id', 'session_date']);
            $table->index(['class_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};