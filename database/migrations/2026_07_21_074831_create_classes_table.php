<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();

            // Nullable: kelas reguler mengisi ini, kelas/kontrak privat mengisi
            // private_package_id di bawah (salah satu wajib terisi — divalidasi
            // di controller/service, bukan di database).
            $table->foreignId('program_package_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('private_package_id')
                ->nullable()
                ->constrained('private_packages')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('level_id')
                ->nullable()
                ->constrained('program_levels')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('class_name');

            $table->enum('delivery_mode', ['Offline', 'Online']);

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->unsignedTinyInteger('capacity');

            $table->enum('status', ['Open', 'Running', 'Completed', 'Closed'])->default('Open');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};