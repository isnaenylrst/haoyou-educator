<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Kolom tambahan hanya dibuat kalau belum ada
        Schema::table('vocab_words', function (Blueprint $t) {
            if (! Schema::hasColumn('vocab_words', 'is_extra'))   $t->boolean('is_extra')->default(false);
            if (! Schema::hasColumn('vocab_words', 'is_tingxie')) $t->boolean('is_tingxie')->default(false);
        });

        Schema::table('vocab_chapters', function (Blueprint $t) {
            if (! Schema::hasColumn('vocab_chapters', 'title'))         $t->string('title')->nullable();
            if (! Schema::hasColumn('vocab_chapters', 'worksheet_url')) $t->string('worksheet_url', 500)->nullable();
            if (! Schema::hasColumn('vocab_chapters', 'dialog'))        $t->mediumText('dialog')->nullable();
        });

        Schema::create('grammar_points', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('program_level_id')->nullable()->index();
            $t->unsignedBigInteger('material_id')->nullable()->index();
            $t->unsignedTinyInteger('chapter_number')->default(1);
            $t->unsignedSmallInteger('order_number')->default(0);
            $t->string('hanzi');
            $t->string('arti')->nullable();
            $t->string('pinyin')->nullable();
            $t->text('fungsi')->nullable();
            $t->text('pola')->nullable();
            $t->json('contoh')->nullable();     // [[hanzi, arti], ...] maksimal 3
            $t->timestamps();

            $t->unique(['program_level_id', 'chapter_number', 'hanzi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grammar_points');
    }
};