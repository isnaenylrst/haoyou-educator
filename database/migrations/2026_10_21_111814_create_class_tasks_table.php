
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_tasks', function (Blueprint $table) {

            $table->id();

            // Relasi kelas
            $table->foreignId('class_id')
                ->constrained('classes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Relasi guru
            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Informasi tugas
            $table->string('title');

            $table->text('description')->nullable();

            $table->date('due_date')->nullable();

            // Status tugas
            $table->enum('status', [
                'Open',
                'Done'
            ])->default('Open');

            $table->timestamp('done_at')->nullable();

            $table->text('teacher_note')->nullable();

            // Lampiran tugas
            $table->string('attachment_path')->nullable();

            $table->string('attachment_name')->nullable();

            // User yang membuat tugas
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();

            // Index untuk pencarian dan filter
            $table->index(
                ['status', 'due_date'],
                'class_tasks_status_due_idx'
            );

            $table->index(
                ['teacher_id', 'status'],
                'class_tasks_teacher_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_tasks');
    }
};
