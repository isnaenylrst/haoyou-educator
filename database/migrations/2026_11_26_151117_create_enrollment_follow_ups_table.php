<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('class_enrollments')->cascadeOnDelete();
            $table->string('status', 40);
            $table->date('followed_up_at');
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['enrollment_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_follow_ups');
    }
};