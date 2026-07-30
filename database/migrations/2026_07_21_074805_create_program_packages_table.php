<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_packages', function (Blueprint $table) {

            $table->id();

            $table->foreignId('program_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('course_type',[
                'Regular',
                'Private'
            ]);

            $table->string('package_name');

            $table->integer('duration_minutes');

            $table->integer('total_meetings');

            $table->integer('min_students');

            $table->integer('max_students');

            $table->decimal('price',12,2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_packages');
    }
};