<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('candidate_student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('kode_siswa')->unique();

            $table->date('tanggal_gabung');

            $table->enum('status', [
                'Aktif',
                'Cuti',
                'Lulus',
                'Alumni'
            ])->default('Aktif');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};