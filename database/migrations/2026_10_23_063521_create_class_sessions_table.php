<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rencana pertemuan (satu baris per pertemuan, bertanggal).
     */
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {

            $table->id();

            // Kelas induk. Nullable karena kelas pengganti (kind = Replacement) tidak punya kelas induk.
            $table->foreignId('class_id')
                ->nullable()
                ->constrained('classes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Pola mingguan yang menjadi sumber pertemuan ini (null untuk pertemuan manual / pengganti)
            $table->foreignId('class_schedule_id')
                ->nullable()
                ->constrained('class_schedules')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Pertemuan rutin atau kelas pengganti
            $table->enum('kind', ['Regular', 'Replacement'])
                ->default('Regular');

            // Pertemuan ke-N dalam paket (tetap melekat walau tanggalnya dipindah)
            $table->unsignedSmallInteger('session_number')
                ->nullable();

            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->string('room')->nullable();

            // Guru terjadwal (guru yang sebenarnya mengajar tetap dicatat di teaching_journals)
            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Lokasi pertemuan. Home Visit = guru datang ke rumah murid (kena biaya tambahan)
            $table->enum('location_type', ['Studio', 'Online', 'Home Visit'])
                ->default('Studio');

            $table->enum('status', ['Scheduled', 'Completed', 'Cancelled'])
                ->default('Scheduled');

            // Tanggal asal kalau pertemuan ini pernah dipindah
            $table->date('original_date')->nullable();
            $table->string('change_reason')->nullable();

            $table->timestamps();

            // Nomor pertemuan unik per kelas (NULL pada kelas pengganti tidak bentrok)
            $table->unique(['class_id', 'session_number']);

            // Pengecekan bentrok guru dan tampilan jadwal
            $table->index(['teacher_id', 'session_date']);
            $table->index(['class_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};