<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_details', function (Blueprint $table) {

            $table->id();

            $table->foreignId('payment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('termin_ke');

            $table->date('tanggal_bayar');

            $table->decimal('nominal',12,2);

            $table->enum('metode',[
                'Cash',
                'Transfer',
                'QRIS'
            ]);

            $table->string('bukti_transfer')->nullable();

            $table->string('reference_number')->nullable();

            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_details');
    }
};