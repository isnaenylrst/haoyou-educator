<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {

            $table->id();

            // Jurnal mengajar yang menjadi induk absensi
            $table->foreignId('teaching_journal_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Siswa
            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Status kehadiran
            $table->enum('status', [
                'Present',
                'Absent',
                'Sick',
                'Permission'
            ]);

            // Catatan jika diperlukan
            $table->text('remarks')->nullable();

            $table->timestamps();

            // Satu siswa hanya boleh memiliki satu absensi pada satu jurnal
            $table->unique([
                'teaching_journal_id',
                'student_id'
            ]);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};