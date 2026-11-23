<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_journal_unlocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_schedule_id')
                ->constrained('class_schedules')
                ->cascadeOnDelete();

            $table->date('session_date');

            // Status permintaan pembukaan kunci
            $table->string('status', 20)->default('Unlocked');

            $table->text('request_reason')->nullable();
            $table->dateTime('requested_at')->nullable();

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Informasi pembukaan kunci
            $table->dateTime('unlocked_until')->nullable();

            $table->foreignId('unlocked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('rejection_note')->nullable();

            $table->timestamps();

            $table->unique(
                ['class_schedule_id', 'session_date'],
                'sched_unlock_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_journal_unlocks');
    }
};