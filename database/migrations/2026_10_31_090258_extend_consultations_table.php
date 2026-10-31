<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Jenis konsultasi baru
        Schema::table('consultations', function (Blueprint $table) {
            $table->enum('type', ['LP_PPT', 'Direktur', 'Trial_Teaching', 'Materi', 'Materi_Baru'])->change();
        });

        // 2) Guru boleh belum terdaftar (trial teaching): nama diketik manual.
        //    Kelas dan deadline dipakai Konsultasi Materi Baru (H-2 minggu).
        Schema::table('consultations', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_id')->nullable()->change();
            $table->string('teacher_name', 150)->nullable()->after('teacher_id');
            $table->string('teacher_phone', 30)->nullable()->after('teacher_name');
            $table->foreignId('class_id')->nullable()->after('teacher_phone')
                ->constrained('classes')->nullOnDelete();
            $table->date('deadline_at')->nullable()->after('scheduled_at');
            $table->index(['status', 'deadline_at'], 'consultations_status_deadline_idx');
        });

        // 3) Isi nama guru untuk data lama
        DB::table('consultations')
            ->join('teachers', 'teachers.id', '=', 'consultations.teacher_id')
            ->update(['consultations.teacher_name' => DB::raw('teachers.name')]);
    }

    public function down(): void
    {
        // Baris yang memakai fitur baru dibuang agar skema lama bisa dipulihkan
        DB::table('consultations')
            ->where(function ($q) {
                $q->whereNull('teacher_id')->orWhereIn('type', ['Materi', 'Materi_Baru']);
            })
            ->delete();

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropIndex('consultations_status_deadline_idx');
            $table->dropConstrainedForeignId('class_id');
            $table->dropColumn(['teacher_name', 'teacher_phone', 'deadline_at']);
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_id')->nullable(false)->change();
            $table->enum('type', ['LP_PPT', 'Direktur', 'Trial_Teaching'])->change();
        });
    }
};