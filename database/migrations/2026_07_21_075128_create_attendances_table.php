
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {

            $table->id();

            // Jurnal mengajar yang menjadi induk absensi
            $table->foreignId('teaching_journal_id')
                ->constrained('teaching_journals')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Siswa
            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Tanggal absensi
            $table->date('attendance_date')->nullable();

            $table->date('session_date');

            // Status kehadiran
            $table->enum('status', [
                'Present',
                'Absent',
                'Sick',
                'Permission'
            ]);

            // Catatan jika diperlukan
            $table->text('note')->nullable();

            $table->timestamps();

            // Satu siswa hanya boleh memiliki satu absensi pada satu jurnal
            $table->unique([
                'teaching_journal_id',
                'student_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
