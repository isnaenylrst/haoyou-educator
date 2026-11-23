
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

            // Relasi guru (boleh kosong untuk guru trial yang belum terdaftar)
            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnDelete();

            $table->string('teacher_name', 150)->nullable();

            $table->string('teacher_phone', 30)->nullable();

            // Relasi kelas
            $table->foreignId('class_id')
                ->nullable()
                ->constrained('classes')
                ->nullOnDelete();

            // Materi guru yang digunakan untuk konsultasi
            $table->foreignId('teacher_material_id')
                ->nullable()
                ->constrained('teacher_materials')
                ->nullOnDelete();

            // Jenis konsultasi
            $table->enum('type', [
                'LP_PPT',
                'Direktur',
                'Trial_Teaching',
                'Materi',
                'Materi_Baru',
            ]);

            $table->dateTime('scheduled_at')->nullable();

            $table->date('deadline_at')->nullable();

            // Status konsultasi
            $table->enum('status', [
                'Belum Terjadwal',
                'Terjadwal',
                'Selesai',
                'Dibatalkan',
            ])->default('Belum Terjadwal');

            $table->boolean('notify_whatsapp')->default(true);

            $table->text('notes')->nullable();

            // User yang membuat konsultasi
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Index untuk pencarian dan filter
            $table->index(
                ['status', 'deadline_at'],
                'consultations_status_deadline_idx'
            );

            $table->index(
                ['status', 'scheduled_at'],
                'consultations_status_scheduled_idx'
            );

            $table->index(
                ['teacher_id', 'status'],
                'consultations_teacher_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
