<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("vocab_chapters", function (Blueprint $t) {
            $t->id();
            $t->foreignId("program_level_id")->constrained("program_levels")->cascadeOnDelete();
            $t->unsignedSmallInteger("number");          // bab ke-berapa
            $t->string("title")->nullable();
            $t->timestamps();
            $t->unique(["program_level_id", "number"]);
        });

        Schema::table("vocab_words", function (Blueprint $t) {
            // model VocabWord sudah punya level(), kolom dibuat hanya jika belum ada
            if (! Schema::hasColumn("vocab_words", "program_level_id")) {
                $t->foreignId("program_level_id")->nullable()->constrained("program_levels")->nullOnDelete();
            }
            $t->foreignId("vocab_chapter_id")->nullable()->constrained("vocab_chapters")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table("vocab_words", function (Blueprint $t) {
            $t->dropConstrainedForeignId("vocab_chapter_id");
        });
        Schema::dropIfExists("vocab_chapters");
    }
};