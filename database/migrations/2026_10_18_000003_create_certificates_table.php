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

            // SESUAIKAN nama tabel jika berbeda dengan project Anda.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('program_level_id')->constrained('program_levels')->cascadeOnDelete();
            $table->unsignedBigInteger('progress_report_id')->nullable();

            $table->string('certificate_number')->unique();
            $table->uuid('verification_code')->unique();

            // Snapshot data saat sertifikat dibuat, supaya sertifikat lama tidak berubah
            // walaupun nama / nilai di tabel lain diedit kemudian.
            $table->string('student_name');
            $table->string('level_name', 50);
            $table->unsignedTinyInteger('level_number');
            $table->json('scores');                      // {"listening":92,"reading":95}
            $table->unsignedSmallInteger('total_score');
            $table->unsignedSmallInteger('max_total');
            $table->unsignedSmallInteger('passing_score');
            $table->enum('result', ['passed', 'not_passed']);

            $table->timestamp('issued_at');
            $table->string('file_path')->nullable();
            $table->timestamps();

            // Aturan: hanya satu sertifikat per siswa per level.
            $table->unique(['user_id', 'program_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
