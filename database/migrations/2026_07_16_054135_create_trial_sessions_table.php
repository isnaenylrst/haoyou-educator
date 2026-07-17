<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_sessions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('candidate_student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('recommended_program_id')
                ->nullable()
                ->constrained('programs')
                ->nullOnDelete();

            $table->date('tanggal_trial');

            $table->enum('status', [
                'Hadir',
                'Tidak Hadir',
                'Reschedule'
            ]);

            $table->text('hasil_trial')->nullable();

            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_sessions');
    }
};