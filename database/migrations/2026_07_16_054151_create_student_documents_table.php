<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table) {

            $table->id();

            $table->foreignId('candidate_student_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('student_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('document_type', [
                'Agreement scan',
                'Payment Proof',
                'Certificate',
                'Identity Card',
                'Family Card',
                'Other'
            ]);

            $table->string('document_name');

            $table->string('file_path');

            $table->timestamp('uploaded_at')->useCurrent();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};