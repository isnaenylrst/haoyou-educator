<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jam kosong Laoshi per minggu (berulang).
 * Dipakai tab "Kelas Privat" -> "Slot Kosong Laoshi".
 * Diisi oleh admin / Laoshi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_available_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // sama dengan class_schedules.day: Senin, Selasa, ... Minggu
            $table->string('day');
            $table->time('start_time');
            $table->time('end_time');

            $table->enum('delivery_mode', ['Online', 'Offline', 'Both'])->default('Both');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['teacher_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_available_slots');
    }
};
