<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->enum('document_type', [
                'Agreement',
                'Progress Report',
                'Certificate',
                'Invoice',
                'Teacher Leave',
                'SOP',
                'Other'
            ]);

            $table->string('file_path');

            $table->text('description')->nullable();

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('status', [
                'Active',
                'Inactive'
            ])->default('Active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};