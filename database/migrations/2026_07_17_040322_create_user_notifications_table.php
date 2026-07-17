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
        Schema::create('notifications', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title');

            $table->text('message');

            $table->enum('type',[
                'lesson_plan',
                'ppt',
                'consultation',
                'progress_report',
                'leave_request',
                'official_letter',
                'system'
            ]);

            $table->boolean('is_read')
                ->default(false);

            $table->timestamps();

            $table->softDeletes();

            $table->index('user_id');
            $table->index('type');
            $table->index('is_read');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};