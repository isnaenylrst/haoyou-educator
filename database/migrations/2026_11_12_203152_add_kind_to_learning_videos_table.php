<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::table('learning_videos', function (Blueprint $table) {
       $table->string('kind', 20)->default('culture')->after('source_type'); // culture / replay
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_videos', function (Blueprint $table) {
        $table->dropColumn('kind');
    });
    }
};
