<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tagihan kelas pengganti yang terbit sebelum pembayaran.
     * Sesi pengganti dan pembayarannya baru dibuat saat dibayar.
     */
    public function up(): void
    {
        Schema::create('replacement_bills', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('class_enrollment_id')->index();

            $table->json('session');

            $table->decimal('replacement_amount', 12, 2);
            $table->decimal('home_visit_amount', 12, 2)->nullable();

            $table->date('due_date');

            $table->string('invoice_number', 60)->unique();

            $table->timestamp('issued_at')->nullable();

            $table->unsignedBigInteger('class_session_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replacement_bills');
    }
};
