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
        if (!Schema::hasTable('owner_action_logs')) {
            Schema::create('owner_action_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role_area', 50); // Admin, Curriculum, Teacher
                $table->string('action', 255);
                $table->string('method', 10);
                $table->string('url', 500);
                $table->text('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('signature_histories')) {
            Schema::create('signature_histories', function (Blueprint $table) {
                $table->id();
                $table->string('document_type', 50); // progress_report, certificate, submission
                $table->unsignedBigInteger('document_id');
                $table->foreignId('signer_id')->constrained('users')->cascadeOnDelete();
                $table->string('signer_role', 50); // Owner, Curriculum
                $table->string('signature_path', 255)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('signed_at')->useCurrent();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['document_type', 'document_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_histories');
        Schema::dropIfExists('owner_action_logs');
    }
};

