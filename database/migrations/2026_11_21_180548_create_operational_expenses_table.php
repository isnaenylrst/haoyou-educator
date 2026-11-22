
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel kas masuk
        Schema::create('cash_funds', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('note')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index('date');
        });

        // Tabel pengeluaran operasional
        Schema::create('operational_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('item_name');
            $table->string('category', 50);
            $table->decimal('amount', 15, 2);
            $table->enum('purchase_method', ['Offline', 'Online'])->default('Offline');
            $table->string('purchase_link', 2048)->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_path');

            $table->string('status', 20)->default('Menunggu Owner');

            // Admin
            $table->foreignId('created_by')->constrained('users');
            $table->string('admin_signature_path')->nullable();
            $table->string('admin_signer_name', 120)->nullable();
            $table->timestamp('admin_signed_at')->nullable();

            // Owner
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner_signature_path')->nullable();
            $table->string('owner_signer_name', 120)->nullable();
            $table->text('owner_note')->nullable();
            $table->timestamp('owner_signed_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_expenses');
        Schema::dropIfExists('cash_funds');
    }
};
