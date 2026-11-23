
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_leaves', function (Blueprint $table) {

            $table->id();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

            $table->foreignId('class_schedule_id')
                ->constrained('class_schedules')
                ->cascadeOnDelete();

            $table->date('leave_date');

            $table->enum('leave_type', [
                'Sick',
                'Permission'
            ]);

            $table->string('request_type', 20)->default('Cuti');

            $table->text('reason');

            $table->text('review_note')->nullable();

            $table->string('supporting_document')->nullable();

            $table->foreignId('replacement_teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->nullOnDelete();

            $table->string('replacement_response', 20)->nullable();

            $table->text('replacement_response_note')->nullable();

            $table->timestamp('replacement_responded_at')->nullable();

            $table->enum('status', [
                'Pending',
                'Approved',
                'Rejected'
            ])->default('Pending');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // Cegah pengajuan cuti dobel untuk jadwal dan tanggal yang sama
            $table->unique(['class_schedule_id', 'leave_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_leaves');
    }
};
