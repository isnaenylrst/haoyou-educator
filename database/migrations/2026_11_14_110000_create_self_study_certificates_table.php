<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_study_certificates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();

            $t->unsignedSmallInteger('year');
            $t->unsignedInteger('sequence');
            $t->string('certificate_number', 40)->unique();   // HY/MANDIRI/2026/0001

            $t->string('student_name');
            $t->unsignedBigInteger('program_level_id')->nullable();
            $t->string('level_name')->nullable();

            // snapshot saat terbit (tidak berubah walau siswa belajar lagi)
            $t->unsignedInteger('vocab_stars');
            $t->unsignedInteger('vocab_max');
            $t->unsignedInteger('grammar_stars');
            $t->unsignedInteger('grammar_max');
            $t->unsignedTinyInteger('vocab_pct');
            $t->unsignedTinyInteger('grammar_pct');
            $t->unsignedTinyInteger('total_score');
            $t->string('predicate', 30);
            $t->unsignedTinyInteger('weight_vocab');
            $t->unsignedTinyInteger('weight_grammar');
            $t->unsignedTinyInteger('passing_score');

            $t->date('period_start');
            $t->date('period_end');
            $t->timestamp('issued_at');
            $t->timestamps();

            $t->unique(['year', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_study_certificates');
    }
};