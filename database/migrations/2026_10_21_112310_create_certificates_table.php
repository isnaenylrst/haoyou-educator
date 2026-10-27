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

            // Satu sertifikat per pendaftaran kelas
            $table->foreignId('enrollment_id')
                ->unique()
                ->constrained('class_enrollments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('certificate_number', 50);

            // Terisi otomatis dari jurnal pertemuan & nilai progress report
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('score', 5, 1)->nullable();

            $table->string('file_path')->nullable();

            // Pending (menunggu Kurikulum) | Approved (tampil di siswa) | Rejected (guru ajukan ulang)
            $table->string('status', 20)->default('Pending');
            $table->text('review_note')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};