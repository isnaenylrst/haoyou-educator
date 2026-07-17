<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_agreements', function (Blueprint $table) {

            $table->id();

            $table->foreignId('candidate_student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('agreement_number')->unique();

            $table->string('agreement_version');

            $table->string('agreement_pdf');

            $table->string('signed_by')->nullable();

            $table->date('signed_date')->nullable();

            $table->enum('status', [
                'Pending',
                'Signed',
                'Rejected'
            ])->default('Pending');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_agreements');
    }
};