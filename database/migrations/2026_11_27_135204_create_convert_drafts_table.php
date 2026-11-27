<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Draf konversi calon siswa: pilihan step 1 + tagihan step 2 (skema, diskon, jatuh tempo, nomor invoice).
     * Siswa/enrollment baru dibuat saat DP dibayar; sebelum itu semuanya disimpan di sini.
     */
    public function up(): void
    {
        Schema::create('convert_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_student_id')->unique();
            $table->json('selection')->nullable();
            $table->string('payment_scheme', 10)->nullable();
            $table->decimal('discount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('form_manual')->nullable();
            $table->string('invoice_number', 40)->nullable()->unique();
            $table->timestamp('invoice_issued_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convert_drafts');
    }
};