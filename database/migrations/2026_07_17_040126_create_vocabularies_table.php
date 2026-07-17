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
        Schema::create('vocabularies', function (Blueprint $table) {

            $table->id();

            $table->foreignId('learning_material_id')
                ->constrained('learning_materials')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('hanzi');

            $table->string('pinyin');

            $table->string('meaning');

            $table->text('example')->nullable();

            $table->timestamps();

            $table->index('learning_material_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vocabularies');
    }
};