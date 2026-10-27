<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sertifikat ujian tidak punya pendaftaran/guru/tanggal kelas,
        // jadi kolom milik sertifikat kelas dibuat nullable.
        Schema::table('certificates', function (Blueprint $table) {
            $table->unsignedBigInteger('enrollment_id')->nullable()->change();
            $table->unsignedBigInteger('student_id')->nullable()->change();
            $table->unsignedBigInteger('teacher_id')->nullable()->change();
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });

        // Kolom sertifikat ujian / progress report
        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')
                ->constrained('users')->cascadeOnDelete();
            $table->foreignId('program_level_id')->nullable()->after('user_id')
                ->constrained('program_levels')->cascadeOnDelete();
            $table->unsignedBigInteger('progress_report_id')->nullable()->after('program_level_id');

            $table->uuid('verification_code')->nullable()->unique();
            $table->string('student_name')->nullable();
            $table->string('level_name', 50)->nullable();
            $table->unsignedTinyInteger('level_number')->nullable();
            $table->json('scores')->nullable();
            $table->unsignedSmallInteger('total_score')->nullable();
            $table->unsignedSmallInteger('max_total')->nullable();
            $table->unsignedSmallInteger('passing_score')->nullable();
            $table->enum('result', ['passed', 'not_passed'])->nullable();
            $table->timestamp('issued_at')->nullable();

            // Satu sertifikat ujian per siswa per level (NULL tidak dihitung bentrok)
            $table->unique(['user_id', 'program_level_id']);
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'program_level_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('program_level_id');
            $table->dropUnique(['verification_code']);
            $table->dropColumn([
                'progress_report_id', 'verification_code', 'student_name', 'level_name',
                'level_number', 'scores', 'total_score', 'max_total', 'passing_score',
                'result', 'issued_at',
            ]);
        });
    }
};