<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_enrollments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('class_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('private_package_id')
                ->nullable()
                ->constrained('private_packages')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->date('enrollment_date');

            $table->enum('status', ['Active', 'Completed', 'Cancelled'])->default('Active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_enrollments');
    }
};