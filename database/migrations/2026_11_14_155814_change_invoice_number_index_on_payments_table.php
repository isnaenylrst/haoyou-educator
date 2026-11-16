<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoice gabungan Home Visit + Pertemuan Pengganti dicatat sebagai dua baris payments
 * dengan nomor invoice yang sama, jadi indeks unik invoice_number diganti indeks biasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique('payments_invoice_number_unique');
            });
        } catch (\Throwable $e) {
            // indeks unik tidak ada: tidak perlu diubah
        }

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('invoice_number');
            });
        } catch (\Throwable $e) {
            // indeks sudah ada
        }
    }

    public function down(): void
    {
        // tidak dikembalikan ke unique karena data gabungan akan bentrok
    }
};