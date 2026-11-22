<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_learning_videos', function (Blueprint $t) {
            $t->id();
            $t->string('module', 20);               // vocab | grammar
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('source_type', 20);          // youtube | upload
            $t->string('youtube_url')->nullable();
            $t->string('youtube_id', 20)->nullable();
            $t->string('file_path')->nullable();
            $t->string('mime_type')->nullable();
            $t->unsignedBigInteger('file_size')->nullable();
            $t->boolean('is_active')->default(false);
            $t->unsignedBigInteger('uploaded_by')->nullable();
            $t->timestamps();

            $t->index(['module', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_learning_videos');
    }
};