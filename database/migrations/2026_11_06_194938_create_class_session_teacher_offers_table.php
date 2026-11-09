<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengajuan pertemuan (kelas pengganti) ke guru. Sama seperti class_teacher_offers,
     * tapi per pertemuan: class_sessions.teacher_id baru terisi setelah satu guru menerima.
     */
    public function up(): void
    {
        Schema::create('class_session_teacher_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained('class_sessions')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->enum('status', ['Pending', 'Accepted', 'Rejected', 'Closed'])->default('Pending');
            $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['class_session_id', 'teacher_id']);
            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_session_teacher_offers');
    }
};