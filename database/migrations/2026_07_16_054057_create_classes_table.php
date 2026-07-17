<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('program_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('kategori_kelas',[
                'Maochong',
                'Jianer',
                'Hudie',
                'Feixiang',
                'HSK',
                'Private',
                'Bisnis',
                'TOCFL'
            ]);

            $table->foreignId('teacher_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('nama_kelas');

            $table->string('level')->nullable();

            $table->unsignedTinyInteger('kapasitas');

            $table->enum('status',[
                'Baru',
                'Belum Mulai',
                'Berjalan',
                'Selesai'
            ])->default('Baru');

            $table->enum('siklus_progress_report',[
                '2 Bulan',
                '3 Bulan'
            ])->default('3 Bulan');

            $table->date('tanggal_mulai')->nullable();

            $table->date('tanggal_selesai')->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};