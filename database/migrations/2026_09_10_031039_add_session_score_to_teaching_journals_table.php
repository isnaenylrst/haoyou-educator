<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            if (!Schema::hasColumn('teaching_journals', 'session_score')) {
                $table->unsignedTinyInteger('session_score')->nullable()->after('results');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            if (Schema::hasColumn('teaching_journals', 'session_score')) {
                $table->dropColumn('session_score');
            }
        });
    }
};