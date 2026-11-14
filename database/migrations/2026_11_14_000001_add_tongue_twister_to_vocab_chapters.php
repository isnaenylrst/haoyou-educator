<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vocab_chapters', function (Blueprint $t) {
            if (! Schema::hasColumn('vocab_chapters', 'tongue_twister')) {
                $t->mediumText('tongue_twister')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vocab_chapters', function (Blueprint $t) {
            if (Schema::hasColumn('vocab_chapters', 'tongue_twister')) {
                $t->dropColumn('tongue_twister');
            }
        });
    }
};