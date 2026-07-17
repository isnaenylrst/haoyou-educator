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
        Schema::create('leave_requests', function (Blueprint $table) {

            $table->id();

            // Guru yang mengajukan izin
            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Jadwal yang ditinggalkan
            $table->foreignId('class_schedule_id')
                ->constrained('class_schedules')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Guru pengganti (boleh kosong)
            $table->foreignId('replacement_teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Jenis izin
            $table->enum('type',[
                'Sick',
                'Leave',
                'Permission',
                'Emergency',
                'Other'
            ]);

            // Alasan
            $table->text('reason');

            // Status persetujuan
            $table->enum('status',[
                'Pending',
                'Approved',
                'Rejected'
            ])->default('Pending');

            // Kepala Kurikulum yang menyetujui
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('curriculums')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Waktu persetujuan
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('teacher_id');
            $table->index('replacement_teacher_id');
            $table->index('approved_by');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};