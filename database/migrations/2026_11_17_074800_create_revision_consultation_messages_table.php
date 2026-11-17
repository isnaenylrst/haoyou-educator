<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('revision_consultation_messages')) {
            Schema::create('revision_consultation_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('revision_consultation_id')->constrained('revision_consultations')->cascadeOnDelete();
                $table->string('sender_type', 30); // 'curriculum' atau 'teacher'
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('message');
                $table->string('attachment_path')->nullable();
                $table->string('attachment_name')->nullable();
                $table->string('decision', 50)->nullable(); // 'Terima', 'Perlu Revisi', 'Upload Ulang', dll
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revision_consultation_messages');
    }
};
