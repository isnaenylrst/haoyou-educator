<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur "Belajar Vocab" (Study Plan, Vocab List, Test, Vocab Traps).
 *
 * TIDAK memakai materials_vocab karena tabel itu = vocab per materi pertemuan
 * (tinyint order, tanpa Inggris / jenis kata). Bank vocab Huahua dibuat terpisah.
 * Yang dipakai ulang dari database lama: users (progres per siswa) & program_levels (level HSK).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bank vocab (master) — 1 baris = 1 kata
        Schema::create('vocab_words', function (Blueprint $t) {
            $t->id();
            $t->foreignId('program_level_id')->nullable()
              ->constrained('program_levels')->cascadeOnUpdate()->nullOnDelete(); // mis. HSK 5
            $t->unsignedSmallInteger('order_number')->index();
            $t->string('hanzi');
            $t->string('pinyin');
            $t->string('meaning_id');                 // Arti (Indo)
            $t->string('meaning_en')->nullable();     // Meaning (English)
            $t->string('word_type', 30)->nullable();  // 动 名 形 副 ...
            $t->string('audio_path')->nullable();     // kosong => pakai suara browser
            $t->boolean('trap_only')->default(false); // tanda * : tidak ikut Test, hanya di Vocab Traps
            $t->timestamps();
            $t->index('hanzi');
        });

        // Banyak contoh kalimat per kata (Vocab List & Pratinjau Vocab Traps)
        Schema::create('vocab_examples', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vocab_word_id')->constrained('vocab_words')->cascadeOnDelete();
            $t->string('sentence_hanzi');
            $t->string('sentence_pinyin')->nullable();
            $t->string('sentence_meaning');
            $t->unsignedTinyInteger('sort_order')->default(0);
            $t->timestamps();
        });

        // Penanda kata yang masuk Vocab Traps
        Schema::create('vocab_traps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vocab_word_id')->constrained('vocab_words')->cascadeOnDelete();
            $t->enum('type', ['same_meaning', 'same_hanzi']);   // arti sama / hanzi sama
            $t->string('group_key')->nullable();                // pengelompokan pasangan
            $t->json('explanations')->nullable();               // ["poin 1","poin 2"]
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
            $t->unique(['vocab_word_id', 'type']);
        });

        // Hasil Test per siswa per kata per jenis test.
        // Bintang di Vocab List = jumlah test_type berstatus 'hafal' (0-3).
        Schema::create('vocab_test_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('vocab_word_id')->constrained('vocab_words')->cascadeOnDelete();
            $t->enum('test_type', ['reading', 'listening', 'writing']);
            $t->string('answer')->nullable();        // reading/listening: arti | writing: pinyin
            $t->string('answer_extra')->nullable();  // writing: hanzi
            $t->boolean('is_ragu')->default(false);
            $t->enum('status', ['hafal', 'ragu', 'salah']);
            $t->timestamps();
            $t->unique(['user_id', 'vocab_word_id', 'test_type']);
            $t->index(['user_id', 'test_type', 'status']);
        });

        // Centang "Paham" di Vocab Traps
        Schema::create('vocab_trap_understood', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('vocab_word_id')->constrained('vocab_words')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'vocab_word_id']);
        });

        // Study Plan (1 siswa = 1 rencana aktif)
        Schema::create('vocab_study_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $t->date('start_date');
            $t->unsignedSmallInteger('words_per_day');     // diketik sendiri
            $t->date('end_date');                          // target siswa
            $t->unsignedSmallInteger('daily_minutes');     // dropdown 5 menit - 3 jam
            $t->unsignedSmallInteger('duration_days');     // otomatis
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['vocab_study_plans','vocab_trap_understood','vocab_test_results','vocab_traps','vocab_examples','vocab_words'] as $tb) {
            Schema::dropIfExists($tb);
        }
    }
};
