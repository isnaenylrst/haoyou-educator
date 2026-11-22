<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_study_settings', function (Blueprint $t) {
            $t->id();
            $t->unsignedTinyInteger('weight_vocab')->default(70);
            $t->unsignedTinyInteger('weight_grammar')->default(30);
            $t->unsignedTinyInteger('passing_score')->default(75);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        // isi awal diambil dari config/selfstudy.php
        DB::table('self_study_settings')->insert([
            'id'             => 1,
            'weight_vocab'   => (int) config('selfstudy.weight_vocab', 70),
            'weight_grammar' => (int) config('selfstudy.weight_grammar', 30),
            'passing_score'  => (int) config('selfstudy.passing_score', 75),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('self_study_settings');
    }
};