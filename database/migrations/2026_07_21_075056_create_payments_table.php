<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('enrollment_id')
                ->constrained('class_enrollments')
                ->cascadeOnDelete();

            $table->string('invoice_number')->unique();

            $table->string('invoice_file_path')->nullable();

            $table->string('payment_stage');

            $table->decimal('total_bill',12,2);

            $table->decimal('amount_paid',12,2);

            $table->decimal('remaining_bill',12,2);

            $table->string('payment_method');

            $table->date('payment_date');

            $table->string('payment_proof_path')->nullable();

            $table->enum('status',[
                'Pending',
                'Paid',
                'Partial'
            ])->default('Pending');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};