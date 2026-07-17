<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {

            $table->id();

            $table->foreignId('candidate_student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('template_id')
                ->constrained('followup_templates')
                ->restrictOnDelete();

            $table->foreignId('admin_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->dateTime('tanggal_followup');

            $table->enum('media', [
                'WhatsApp',
                'Telepon',
                'Offline',
                'Instagram',
                'Email'
            ]);

            $table->text('hasil_followup')->nullable();

            $table->dateTime('next_followup')->nullable();

            $table->text('notes')->nullable();

            $table->enum('status', [
                'Pending',
                'Done',
                'Cancelled'
            ])->default('Pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};