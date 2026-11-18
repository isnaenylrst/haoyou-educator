<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->date('session_date');
            $t->enum('type', ['kuning', 'merah']);
            $t->unsignedInteger('points');
            $t->integer('points_before');
            $t->integer('points_after');
            $t->foreignId('input_by')->constrained('users');
            $t->enum('input_role', ['admin', 'guru']);
            $t->foreignId('teaching_journal_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();

            $t->unique(['student_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_logs');
    }
};