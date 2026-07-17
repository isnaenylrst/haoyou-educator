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

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('program_price_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('admin_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('invoice_number')->unique();

            $table->string('invoice_pdf')->nullable();
            
            $table->enum('payment_type', [
                'Full Payment',
                'DP',
                'Termin'
            ]);            

            $table->decimal('total_tagihan',12,2);

            $table->decimal('total_bayar',12,2)->default(0);

            $table->decimal('sisa_tagihan',12,2);

            $table->enum('status',[
                'Pending',
                'DP',
                'Sebagian',
                'Lunas',
                'Overdue',
                'Cancelled'
            ])->default('Pending');

            $table->date('tanggal_invoice');

            $table->date('jatuh_tempo');

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};