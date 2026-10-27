<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Biaya tambahan per pertemuan, terpisah dari tagihan paket (payments):
     * - Replacement: kelas pengganti tambahan (Rp175.000 per pertemuan)
     * - Home Visit : guru mengajar ke rumah murid (Rp50.000 per pertemuan)
     * Dipisah supaya perhitungan sisa tagihan paket (remaining_bill) tidak ikut berubah.
     */
    public function up(): void
    {
        Schema::create('session_fees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_session_id')
                ->constrained('class_sessions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Yang menanggung biaya. Null kalau belum bisa ditentukan (mis. kelas dengan banyak siswa).
            $table->foreignId('class_enrollment_id')
                ->nullable()
                ->constrained('class_enrollments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->enum('fee_type', ['Replacement', 'Home Visit']);
            $table->decimal('amount', 12, 2);

            $table->enum('status', ['Unpaid', 'Paid'])->default('Unpaid');
            $table->date('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_proof_path')->nullable();

            $table->timestamps();

            // Satu jenis biaya per pertemuan
            $table->unique(['class_session_id', 'fee_type']);
            $table->index(['class_enrollment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_fees');
    }
};