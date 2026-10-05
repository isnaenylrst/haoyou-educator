<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_packages', function (Blueprint $table) {
            $table->id();

            $table->string('package_name')->unique(); // VIP, Exclusive
            $table->integer('duration_minutes');
            $table->integer('total_meetings');
            $table->integer('min_students');
            $table->integer('max_students');
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_packages');
    }
};