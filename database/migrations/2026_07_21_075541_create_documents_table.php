<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {

            $table->id();

            // Pemilik dokumen
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Template dokumen
            $table->foreignId('document_template_id')
                ->nullable()
                ->constrained('document_templates')
                ->nullOnDelete();

            // Level program
            $table->foreignId('program_level_id')
                ->nullable()
                ->constrained('program_levels')
                ->nullOnDelete();

            $table->string('title');

            // Jenis dokumen
            $table->enum('document_type', [
                'CV',
                'Photo',
                'Teacher Certificate',
                'Certificate',
                'Agreement',
                'SOP',
                'Teacher Leave Letter',
                'Other',
                'SURAT_LIBUR',
                'SURAT_DINAS',
                'LOA',
            ]);

            $table->text('description')->nullable();

            $table->string('file_path');

            // Hak akses dokumen
            $table->enum('visibility', [
                'Private',
                'Teacher',
                'Student',
                'Public',
            ])->default('Private');

            // User yang mengupload
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamp('uploaded_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};