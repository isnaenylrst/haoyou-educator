<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_prices', function (Blueprint $table) {

            $table->id();

            $table->foreignId('program_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('nama_paket');

            $table->decimal('nominal',12,2);

            $table->enum('payment_scheme', [
                'Full',
                'Termin'
            ]);

            $table->integer('max_termin')->default(1);

            $table->date('effective_from');

            $table->date('effective_until')->nullable();

            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_prices');
    }
};