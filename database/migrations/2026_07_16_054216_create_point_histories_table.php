<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_histories', function (Blueprint $table) {

            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('class_schedule_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('given_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('point', ['5', '10']);

            $table->string('description')->nullable();

            $table->timestamps();

            // Satu siswa hanya boleh mendapat poin sekali pada satu pertemuan
            $table->unique([
                'student_id',
                'class_schedule_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_histories');
    }
};