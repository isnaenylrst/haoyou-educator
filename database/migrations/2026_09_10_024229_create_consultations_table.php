<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {

            $table->id();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

            $table->enum('type', [
                'LP_PPT',
                'Direktur',
                'Trial_Teaching',
            ]);

            $table->dateTime('scheduled_at')->nullable();

            $table->enum('status', [
                'Belum Terjadwal',
                'Terjadwal',
                'Selesai',
                'Dibatalkan',
            ])->default('Belum Terjadwal');

            $table->boolean('notify_whatsapp')->default(true);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};