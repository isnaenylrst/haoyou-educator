<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vocab_test_results', function (Blueprint $t) {
            $t->string('answer_pinyin')->nullable();
            $t->string('answer_tone', 50)->nullable();
            $t->string('answer_hanzi')->nullable();
            $t->string('answer_meaning')->nullable();
        });

        // Opsional: hasil lama memakai format `answer`/`answer_extra` yang sudah tidak dipakai.
        // Hapus komentar baris di bawah kalau mau mengosongkan hasil lama agar tidak tampil HAFAL dengan kolom kosong.
        // \Illuminate\Support\Facades\DB::table('vocab_test_results')->delete();
    }

    public function down(): void
    {
        Schema::table('vocab_test_results', function (Blueprint $t) {
            $t->dropColumn(['answer_pinyin', 'answer_tone', 'answer_hanzi', 'answer_meaning']);
        });
    }
};