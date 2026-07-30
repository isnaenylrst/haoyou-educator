<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('level_id')
                ->constrained('levels', 'id_level')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('username')->unique();
            $table->string('password');
            $table->enum('status', [
                'Active',
                'Inactive'
            ])->default('Active');

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};