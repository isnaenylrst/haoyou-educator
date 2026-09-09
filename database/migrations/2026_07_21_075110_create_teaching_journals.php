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
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Jadwal mengajar (template hari/jam berulang)
            $table->foreignId('class_schedule_id')
                ->constrained('class_schedules')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Tanggal aktual pertemuan iterjadi
            $table->date('session_date');

            // Materi yang diajarkan (guru pilih sendiri dari daftar materi levelnya)
            $table->foreignId('material_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Apakah menggantikan guru lain?
            $table->boolean('is_substitute')
                ->default(false);

            // Guru yang digantikan
            $table->foreignId('substitute_teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Apakah kelas berjalan?
            $table->enum('class_status', [
                'Conducted',
                'Cancelled',
                'Rescheduled'
            ]);

            // Aktivitas pembelajaran
            $table->text('learning_activities');

            // Kendala
            $table->text('problems')->nullable();

            // Solusi
            $table->text('solutions')->nullable();

            // Hasil pembelajaran
            $table->text('results')->nullable();

            // Catatan tambahan
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_journals');
    }
};