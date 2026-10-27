<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris = satu sesi kelas privat pada tanggal tertentu.
 * Reschedule (minimal H-1) cukup mengubah tanggal/jam baris ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // enrollment paket private milik siswa (class_enrollments.private_package_id)
            $table->foreignId('enrollment_id')
                ->constrained('class_enrollments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('teacher_available_slot_id')
                ->nullable()
                ->constrained('teacher_available_slots')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->enum('delivery_mode', ['Online', 'Offline'])->default('Online');
            $table->enum('status', ['Scheduled', 'Completed', 'Cancelled'])->default('Scheduled');

            // jejak reschedule
            $table->date('rescheduled_from_date')->nullable();
            $table->timestamp('rescheduled_at')->nullable();
            $table->unsignedTinyInteger('reschedule_count')->default(0);

            $table->timestamps();

            $table->index(['teacher_id', 'session_date', 'start_time']);
            $table->index(['student_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_bookings');
    }
};
