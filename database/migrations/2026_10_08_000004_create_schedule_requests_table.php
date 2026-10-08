<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Request jadwal dari siswa (privat & reguler).
 * Admin meninjau lalu mengonfirmasi via WhatsApp/Email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('request_type', ['Private', 'Regular']);

            // Reguler: program yang diinginkan
            $table->foreignId('program_id')
                ->nullable()
                ->constrained('programs')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Privat: Laoshi yang diinginkan (opsional)
            $table->foreignId('preferred_teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Privat: tanggal tertentu | Reguler: hari berulang, mis. "Selasa & Kamis"
            $table->date('preferred_date')->nullable();
            $table->string('preferred_days')->nullable();
            $table->time('preferred_start_time')->nullable();

            $table->enum('delivery_mode', ['Online', 'Offline'])->nullable();
            $table->text('note')->nullable();

            $table->enum('status', ['Pending', 'Confirmed', 'Rejected'])->default('Pending');
            $table->text('admin_note')->nullable();

            $table->foreignId('handled_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamp('handled_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'request_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_requests');
    }
};
