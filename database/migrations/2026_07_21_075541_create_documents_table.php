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

            $table->foreignId('document_template_id')
                ->nullable()
                ->constrained('document_templates')
                ->nullOnDelete(); 
            
            $table->string('title');

            $table->enum('document_type', [
                'CV',
                'Photo',
                'Teacher Certificate',
                'Agreement',
                'SOP',
                'Teacher Leave Letter',
                'Other'
            ]);

            $table->text('description')->nullable();

            $table->string('file_path');

            $table->enum('visibility', [
                'Private',
                'Teacher',
                'Student',
                'Public'
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