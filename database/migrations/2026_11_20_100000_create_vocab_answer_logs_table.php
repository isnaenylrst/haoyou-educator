<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocab_answer_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->unsignedBigInteger('vocab_word_id')->index();
            $t->string('test_type', 10);                       // reading | listening | writing
            $t->string('answer_pinyin')->nullable();
            $t->string('answer_tone', 50)->nullable();
            $t->string('answer_hanzi')->nullable();
            $t->string('answer_meaning')->nullable();
            $t->string('status', 10);                          // hafal | ragu | salah
            $t->unsignedSmallInteger('attempt_no')->default(1);
            $t->timestamp('answered_at')->useCurrent();

            $t->index(['user_id', 'vocab_word_id', 'test_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_answer_logs');
    }
};