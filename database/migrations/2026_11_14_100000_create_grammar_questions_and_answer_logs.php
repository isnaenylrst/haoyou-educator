<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Bank soal grammar (bisa diimport dari Excel)
        Schema::create('grammar_questions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('program_level_id')->nullable()->index();
            $t->unsignedBigInteger('grammar_point_id')->nullable()->index();
            $t->unsignedTinyInteger('chapter_number')->default(1);
            $t->unsignedSmallInteger('order_number')->default(0);
            $t->string('soal');
            $t->json('options');   // [[hanzi, status], ...] status: benar | kurang | salah
            $t->timestamps();

            $t->foreign('grammar_point_id')->references('id')->on('grammar_points')->nullOnDelete();
            $t->unique(['grammar_point_id', 'soal']);
        });

        // Log tiap jawaban (tidak menimpa). Dipakai kurikulum untuk analisis.
        Schema::create('grammar_answer_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->unsignedInteger('question_no');                       // item_no soal saat dijawab
            $t->unsignedBigInteger('grammar_point_id')->nullable()->index();
            $t->unsignedTinyInteger('choice');
            $t->string('status', 10);                                 // benar | kurang | salah
            $t->unsignedSmallInteger('attempt_no')->default(1);
            $t->timestamp('answered_at')->useCurrent();

            $t->index(['user_id', 'question_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grammar_answer_logs');
        Schema::dropIfExists('grammar_questions');
    }
};