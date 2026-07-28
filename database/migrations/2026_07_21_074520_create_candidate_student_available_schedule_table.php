<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_student_available_schedules', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('candidate_student_id');

            $table->foreign('candidate_student_id', 'fk_candidate_schedule')
                ->references('id')
                ->on('candidate_students')
                ->cascadeOnDelete();

            $table->enum('day', [
                'Senin',
                'Selasa',
                'Rabu',
                'Kamis',
                'Jumat',
                'Sabtu',
            ]);

            $table->time('start_time');

            $table->time('end_time');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_student_available_schedules');
    }
};