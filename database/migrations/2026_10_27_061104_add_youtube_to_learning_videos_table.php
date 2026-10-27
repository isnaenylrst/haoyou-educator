<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_videos', function (Blueprint $table) {
            $table->string('source_type', 10)->default('file')->after('description'); // file | youtube
            $table->string('youtube_url')->nullable()->after('source_type');
            $table->string('youtube_id', 20)->nullable()->after('youtube_url');
            $table->string('file_path')->nullable()->change(); // video YouTube tidak punya file
        });
    }

    public function down(): void
    {
        DB::table('learning_videos')->where('source_type', 'youtube')->delete();

        Schema::table('learning_videos', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'youtube_url', 'youtube_id']);
            $table->string('file_path')->nullable(false)->change();
        });
    }
};