<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor invoice biaya tambahan, terpisah dari nomor invoice pembayaran paket (payments).
     * Diisi saat biaya ditandai lunas.
     */
    public function up(): void
    {
        Schema::table('session_fees', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->unique()->after('payment_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('session_fees', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn('invoice_number');
        });
    }
};