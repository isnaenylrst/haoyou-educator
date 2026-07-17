<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_students', function (Blueprint $table) {

            $table->id();

            $table->string('nama');
            $table->enum('jenis_kelamin',['L','P']);

            $table->date('tanggal_lahir')->nullable();
            $table->integer('usia')->nullable();

            $table->string('no_hp');
            $table->string('email')->nullable();

            $table->string('nama_ortu')->nullable();
            $table->string('no_hp_ortu')->nullable();

            $table->text('alamat')->nullable();
            $table->string('sekolah')->nullable();

            $table->string('sumber')->nullable();

            $table->string('kebutuhan_belajar')->nullable();

            $table->text('available_schedule')->nullable();

            $table->string('alergi')->nullable();

            $table->text('catatan')->nullable();

            $table->enum('status_lead',[
                'Inquiry',
                'Warm',
                'Hot',
                'Cold',
                'Lost',
                'Converted'
            ])->default('Inquiry');

            $table->enum('status_trial',[
                'Belum',
                'Menunggu',
                'Sudah',
                'Tidak Trial'
            ])->default('Belum');

            $table->date('tanggal_trial')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_students');
    }
};