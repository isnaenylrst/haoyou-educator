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
        Schema::create('official_letters', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title');

            $table->enum('category',[
                'Surat Tugas',
                'Surat Keterangan',
                'Surat Izin',
                'Surat Pengangkatan',
                'Surat Peringatan',
                'Surat Edaran',
                'Lainnya'
            ]);

            $table->text('description')->nullable();

            $table->string('file_path');

            $table->enum('status',[
                'Draft',
                'Published',
                'Archived'
            ])->default('Draft');

            $table->timestamps();

            $table->softDeletes();

            $table->index('user_id');
            $table->index('category');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('official_letters');
    }
};