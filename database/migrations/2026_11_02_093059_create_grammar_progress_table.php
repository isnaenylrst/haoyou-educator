<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grammar_progress', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('kind', 10);
            $t->unsignedInteger('item_no');
            $t->unsignedTinyInteger('choice')->nullable();
            $t->timestamps();

            $t->unique(['user_id', 'kind', 'item_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grammar_progress');
    }
};