<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials_vocab', function (Blueprint $table) {

            $table->id();

            $table->foreignId('material_id')
                ->constrained('materials')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('order_number');
            $table->string('hanzi');
            $table->string('pinyin');
            $table->string('meaning');
            $table->text('example_sentence')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials_vocab');
    }
};