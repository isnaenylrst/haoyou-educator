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
    
    Schema::create('vocab_settings', function (Blueprint $t) {
    $t->id();
    $t->string('key')->unique();
    $t->text('value')->nullable();
    $t->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vocab_settings');
    }
};
