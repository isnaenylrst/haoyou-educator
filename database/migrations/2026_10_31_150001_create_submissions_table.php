
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {

            $table->id();

            // Guru yang mengajukan
            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnDelete();

            // User yang membuat pengajuan
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // Jenis pengajuan
            $table->string('type', 100);

            $table->string('item_name');

            $table->decimal('amount', 14, 2);

            $table->date('date');

            $table->text('description')->nullable();

            // Informasi pembelian
            $table->string('account_number', 60)->nullable();

            $table->string('account_name', 120)->nullable();

            $table->decimal('shipping_cost', 14, 2)
                ->nullable()
                ->default(0);

            $table->foreignId('class_id')
                ->nullable()
                ->constrained('classes')
                ->nullOnDelete();

            // Bukti pengajuan
            $table->string('receipt_path')->nullable();

            // Status pengajuan
            $table->string('status', 50)->default('Diajukan');

            // Review Kurikulum
            $table->foreignId('curriculum_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('curriculum_note')->nullable();

            $table->timestamp('curriculum_approved_at')->nullable();

            $table->string('curriculum_signature_path')->nullable();

            // Review Owner
            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('owner_note')->nullable();

            $table->timestamp('owner_approved_at')->nullable();

            $table->string('owner_signature_path')->nullable();

            // Tanda tangan Admin
            $table->string('admin_signature_path')->nullable();

            $table->string('admin_signer_name', 120)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
