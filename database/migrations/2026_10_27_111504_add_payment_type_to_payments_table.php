<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biaya tambahan (kelas pengganti & home visit) dicatat di tabel payments.
 * - payment_type     : pembeda supaya biaya tambahan TIDAK ikut dihitung ke sisa tagihan paket
 *                      (PaymentInstallmentService::totalPaid() hanya menjumlah type Package).
 * - class_session_id : pertemuan yang dibayar (null untuk pembayaran paket).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_type', ['Package', 'Replacement', 'Home Visit'])
                ->default('Package');

            // nullOnDelete: catatan uang masuk tidak boleh hilang walau pertemuannya dihapus
            $table->foreignId('class_session_id')
                ->nullable()
                ->constrained('class_sessions')
                ->nullOnDelete();

            $table->index(['payment_type', 'class_session_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['class_session_id']);
            $table->dropIndex(['payment_type', 'class_session_id']);
            $table->dropColumn(['payment_type', 'class_session_id']);
        });
    }
};