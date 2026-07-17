<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {

            $table->id();

            $table->string('nama_program');

            $table->enum('tipe_kelas',[
                'Regular',
                'Private',
                'HSK'
            ]);

            $table->string('fokus');

            $table->integer('durasi');

            $table->integer('jumlah_pertemuan');

            $table->integer('min_siswa');

            $table->integer('max_siswa');

            $table->boolean('aktif')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};